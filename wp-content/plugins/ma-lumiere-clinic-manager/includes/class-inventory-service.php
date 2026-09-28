<?php
/**
 * Inventory service: the only sanctioned path for changing pharmacy stock.
 *
 * Every quantity change follows the same shape:
 *
 *   1. pre-flight  — plan the draw (FEFO across live batches) and refuse up
 *                    front if the units do not exist, so a dispense is never
 *                    left half-applied;
 *   2. apply       — mutate batch quantities under an optimistic guard;
 *   3. record      — append the matching rows to the stock movement ledger.
 *
 * If step 2 fails part-way, already-applied batches are reversed with a
 * compensating delta so the batch totals and the ledger stay in agreement.
 *
 * The service holds no stock of its own; it is deliberately thin and
 * delegates all reads to the repositories.
 *
 * @package MaLumiere\Clinic
 */

defined( 'ABSPATH' ) || exit;

class ML_Inventory_Service {

	/**
	 * How many days ahead a batch counts as "expiring soon".
	 *
	 * @var int
	 */
	const EXPIRY_WINDOW_DAYS = 90;

	/**
	 * Receive stock into the clinic: opens a new batch and records the
	 * opening balance as a `purchase` movement.
	 *
	 * @param array $input Raw batch values (see ML_Medicine_Batch_Repository::fields()).
	 *
	 * @return int|WP_Error Batch ID.
	 */
	public static function receive( array $input ) {
		$medicine_id = absint( isset( $input['medicine_id'] ) ? $input['medicine_id'] : 0 );

		$batch_id = ML_Medicine_Batch_Repository::create( $input );
		if ( is_wp_error( $batch_id ) ) {
			return $batch_id;
		}

		$batch = ML_Medicine_Batch_Repository::get( $batch_id );
		$qty   = $batch ? (int) $batch['quantity_received'] : 0;

		$movement = ML_Stock_Movement_Repository::record(
			array(
				'medicine_id'   => $medicine_id,
				'batch_id'      => $batch_id,
				'movement_type' => 'purchase',
				'quantity'      => $qty,
				'balance_after' => $qty,
				'unit_cost'     => $batch ? (float) $batch['unit_cost'] : 0,
				'notes'         => self::receipt_note( $input, $batch ),
			)
		);
		if ( is_wp_error( $movement ) ) {
			// Do not leave a batch without a ledger entry behind it.
			ML_Medicine_Batch_Repository::discard_if_unledgered( $batch_id );
			return $movement;
		}

		ml_audit(
			'medicine_stock_received',
			'medicine',
			$medicine_id,
			sprintf(
				/* translators: 1: quantity, 2: medicine name. */
				__( 'Received %1$d units into a new batch for %2$s.', 'ma-lumiere-clinic' ),
				$qty,
				$batch ? $batch['medicine_label'] : '#' . $medicine_id
			)
		);

		return $batch_id;
	}

	/**
	 * Apply a manual stock change to a single batch: a correction, a wastage
	 * write-off, or an expiry write-off. Positive delta adds stock, negative
	 * removes it. The delta may never take a batch below zero.
	 *
	 * @param int    $batch_id Batch ID.
	 * @param int    $delta    Signed change.
	 * @param string $type     wastage|expired|adjustment|return.
	 * @param string $notes    Reason (required for write-offs and adjustments).
	 *
	 * @return int|WP_Error Resulting batch quantity.
	 */
	public static function adjust( $batch_id, $delta, $type = 'adjustment', $notes = '' ) {
		$batch_id = absint( $batch_id );
		$delta    = (int) $delta;
		$type     = sanitize_key( (string) $type );

		if ( ! in_array( $type, ML_Stock_Movement_Repository::type_keys(), true ) ) {
			$type = 'adjustment';
		}
		if ( 0 === $delta ) {
			return new WP_Error( 'ml_inventory_no_change', __( 'Enter a non-zero quantity to record a stock change.', 'ma-lumiere-clinic' ) );
		}

		$batch = ML_Medicine_Batch_Repository::get( $batch_id );
		if ( ! $batch ) {
			return new WP_Error( 'ml_batch_not_found', __( 'Batch not found.', 'ma-lumiere-clinic' ) );
		}

		// A reduction must always carry a reason so the ledger is meaningful.
		if ( $delta < 0 && '' === trim( (string) $notes ) ) {
			$label = ML_Stock_Movement_Repository::type_label( $type );
			return new WP_Error( 'ml_inventory_reason_required', sprintf( __( 'Please give a reason for this %s.', 'ma-lumiere-clinic' ), mb_strtolower( $label ) ) );
		}

		$balance = ML_Medicine_Batch_Repository::apply_delta( $batch_id, $delta, (int) $batch['quantity_available'] );
		if ( is_wp_error( $balance ) ) {
			return $balance;
		}

		$movement = ML_Stock_Movement_Repository::record(
			array(
				'medicine_id'   => (int) $batch['medicine_id'],
				'batch_id'      => $batch_id,
				'movement_type' => $type,
				'quantity'      => $delta,
				'balance_after' => $balance,
				'unit_cost'     => (float) $batch['unit_cost'],
				'notes'         => $notes,
			)
		);

		if ( is_wp_error( $movement ) ) {
			// Ledger is the source of truth: reverse the quantity so the two
			// cannot diverge.
			ML_Medicine_Batch_Repository::apply_delta( $batch_id, -$delta, $balance );
			return $movement;
		}

		// An expired write-off retires the lot so it is never drawn again.
		if ( 'expired' === $type && $balance <= 0 ) {
			ML_Medicine_Batch_Repository::set_status( $batch_id, 'expired' );
		}

		ml_audit(
			'inventory_adjusted',
			'medicine',
			(int) $batch['medicine_id'],
			sprintf(
				/* translators: 1: signed quantity, 2: medicine name, 3: batch number. */
				__( 'Stock adjusted by %1$d for %2$s (batch %3$s).', 'ma-lumiere-clinic' ),
				$delta,
				$batch['medicine_label'],
				$batch['batch_number'] ? $batch['batch_number'] : '#' . $batch_id
			)
		);

		return $balance;
	}

	/**
	 * Write off every expired unit still sitting in an active batch. Safe to
	 * run repeatedly; already-depleted lots are skipped.
	 *
	 * @return array ['batches' => int, 'units' => int, 'skipped' => array]
	 */
	public static function write_off_expired() {
		global $wpdb;

		$table  = ML_Database::table( 'medicine_batches' );
		$today  = current_time( 'Y-m-d' );
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE status = 'active' AND quantity_available > 0 AND expiry_date IS NOT NULL AND expiry_date < %s",
				$today
			),
			ARRAY_A
		);

		$written = array( 'batches' => 0, 'units' => 0, 'skipped' => array() );

		foreach ( (array) $rows as $row ) {
			$qty = (int) $row['quantity_available'];
			$result = self::adjust(
				(int) $row['id'],
				-$qty,
				'expired',
				sprintf(
					/* translators: %s: expiry date. */
					__( 'Expired on %s.', 'ma-lumiere-clinic' ),
					ml_date( $row['expiry_date'] )
				)
			);

			if ( is_wp_error( $result ) ) {
				$written['skipped'][] = array(
					'batch_id' => (int) $row['id'],
					'error'    => $result->get_error_message(),
				);
				continue;
			}

			$written['batches']++;
			$written['units']  += $qty;
		}

		if ( $written['batches'] > 0 ) {
			ml_audit(
				'inventory_expired_written_off',
				'inventory',
				0,
				sprintf(
					/* translators: 1: number of batches, 2: units. */
					__( 'Wrote off %2$d expired units across %1$d batches.', 'ma-lumiere-clinic' ),
					$written['batches'],
					$written['units']
				)
			);
		}

		return $written;
	}

	/**
	 * Issue stock for a finalized prescription.
	 *
	 * Only items that are linked to a catalogue medicine and carry a
	 * quantity are drawn — a prescription written for a product the clinic
	 * does not stock is left untouched rather than silently zeroed.
	 *
	 * The draw is FEFO: soonest-expiring live batch first, undated lots last.
	 * Idempotent — re-running tops up only what is still outstanding, so a
	 * correction revision can dispense the difference.
	 *
	 * @param int $prescription_id Prescription ID.
	 *
	 * @return array|WP_Error ['dispensed' => int, 'items' => int, 'movements' => int]
	 */
	public static function dispense_prescription( $prescription_id ) {
		global $wpdb;

		$prescription_id = absint( $prescription_id );
		$prescription    = ML_Prescription_Repository::get( $prescription_id );
		if ( ! $prescription ) {
			return new WP_Error( 'ml_prescription_not_found', __( 'Prescription not found.', 'ma-lumiere-clinic' ) );
		}
		if ( 'final' !== (string) $prescription['status'] ) {
			return new WP_Error( 'ml_prescription_not_final', __( 'Stock can only be dispensed for an issued prescription.', 'ma-lumiere-clinic' ) );
		}

		$outstanding = self::outstanding_items( $prescription_id );
		if ( empty( $outstanding['items'] ) ) {
			return array(
				'dispensed' => 0,
				'items'     => 0,
				'movements' => 0,
			);
		}

		/*
		 * Pre-flight: plan every line before touching stock so a shortage on
		 * one medicine cannot leave the others already dispensed.
		 *
		 * The same medicine can appear on more than one line, so the need is
		 * totalled per medicine first and planned once. Planning each line
		 * separately would let two lines each believe the same units are
		 * available.
		 */
		$needs  = array();
		$totals = array();
		foreach ( $outstanding['items'] as $item ) {
			$medicine_id = (int) $item['medicine_id'];
			$need        = (int) $item['need'];
			$needs[ $medicine_id ][] = array(
				'item_id'   => (int) $item['id'],
				'need'      => $need,
				'medicine_name' => (string) $item['medicine_name'],
			);
			$totals[ $medicine_id ] = ( isset( $totals[ $medicine_id ] ) ? $totals[ $medicine_id ] : 0 ) + $need;
		}

		$draws = array();
		$short = array();
		foreach ( $totals as $medicine_id => $total_need ) {
			$draw = ML_Medicine_Batch_Repository::plan_draw( (int) $medicine_id, (int) $total_need );
			$draws[ $medicine_id ] = $draw;

			if ( $draw['shortfall'] > 0 ) {
				$medicine = ML_Medicine_Repository::get( (int) $medicine_id );
				$short[]  = sprintf(
					/* translators: 1: medicine name, 2: needed, 3: available. */
					__( '%1$s: %2$d needed, %3$d in stock.', 'ma-lumiere-clinic' ),
					$medicine ? $medicine['display_name'] : '#' . (int) $medicine_id,
					(int) $total_need,
					$draw['available']
				);
			}
		}

		if ( $short ) {
			return new WP_Error(
				'ml_inventory_insufficient',
				__( 'Not enough stock to dispense this prescription:', 'ma-lumiere-clinic' ),
				array( 'shortages' => $short )
			);
		}

		// Split each medicine's FEFO allocations back over its lines, in order.
		$plan = array();
		foreach ( $needs as $medicine_id => $lines ) {
			$queue = $draws[ $medicine_id ]['allocations'];
			foreach ( $lines as $line ) {
				$remaining = (int) $line['need'];
				while ( $remaining > 0 && ! empty( $queue ) ) {
					$allocation = $queue[0];
					$take       = min( $remaining, (int) $allocation['quantity'] );
					$plan[]     = array(
						'item_id'       => (int) $line['item_id'],
						'medicine_id'   => (int) $medicine_id,
						'medicine_name' => (string) $line['medicine_name'],
						'batch_id'      => (int) $allocation['batch_id'],
						'quantity'      => $take,
						'unit_cost'     => (float) $allocation['unit_cost'],
					);
					$remaining  -= $take;
					$queue[0]['quantity'] = (int) $queue[0]['quantity'] - $take;
					if ( $queue[0]['quantity'] <= 0 ) {
						array_shift( $queue );
					}
				}
			}
		}

		$applied   = array();
		$movements = 0;

		foreach ( $plan as $entry ) {
			$batch = ML_Medicine_Batch_Repository::get( $entry['batch_id'] );
			if ( ! $batch ) {
				self::rollback( $applied );
				return new WP_Error( 'ml_batch_not_found', __( 'A batch went missing while dispensing. Nothing was dispensed.', 'ma-lumiere-clinic' ) );
			}

			$balance = ML_Medicine_Batch_Repository::apply_delta(
				$entry['batch_id'],
				-1 * $entry['quantity'],
				(int) $batch['quantity_available']
			);
			if ( is_wp_error( $balance ) ) {
				self::rollback( $applied );
				return new WP_Error(
					'ml_inventory_conflict',
					__( 'Stock changed while dispensing this prescription. Nothing was dispensed — please try again.', 'ma-lumiere-clinic' ),
					array( 'cause' => $balance->get_error_message() )
				);
			}

			$applied[] = array(
				'item_id'   => (int) $entry['item_id'],
				'batch_id'  => (int) $entry['batch_id'],
				'quantity'  => (int) $entry['quantity'],
				'unit_cost' => (float) $entry['unit_cost'],
			);

			$movement = ML_Stock_Movement_Repository::record(
				array(
					'medicine_id'    => $entry['medicine_id'],
					'batch_id'       => $entry['batch_id'],
					'movement_type'  => 'dispense',
					'quantity'       => -1 * $entry['quantity'],
					'balance_after'  => $balance,
					'unit_cost'      => $entry['unit_cost'],
					'reference_type' => 'prescription',
					'reference_id'   => $prescription_id,
					'notes'          => sprintf(
						/* translators: %s: prescription number. */
						__( 'Dispensed for prescription %s.', 'ma-lumiere-clinic' ),
						$prescription['prescription_number']
					),
				)
			);

			if ( is_wp_error( $movement ) ) {
				self::rollback( $applied );
				return $movement;
			}
			$movements++;

			$applied[ count( $applied ) - 1 ]['movement_id'] = (int) $movement;
		}

		// Reflect the draw on the prescription lines so re-running tops up only
		// the shortfall instead of dispensing again.
		$total = 0;
		foreach ( $outstanding['items'] as $item ) {
			$used = 0;
			foreach ( $applied as $entry ) {
				if ( (int) $entry['item_id'] === (int) $item['id'] ) {
					$used += (int) $entry['quantity'];
				}
			}
			if ( $used <= 0 ) {
				continue;
			}
			$updated = $wpdb->update(
				ML_Database::table( 'prescription_items' ),
				array( 'dispensed_quantity' => (int) $item['dispensed_quantity'] + $used ),
				array( 'id' => (int) $item['id'] ),
				array( '%d' ),
				array( '%d' )
			);
			if ( false === $updated ) {
				/*
				 * The line could not be updated, so unwinding the stock keeps
				 * the prescription and the shelves in agreement. Rolling back
				 * leaves the original quantities intact, so the next attempt
				 * plans from the same starting point.
				 */
				self::rollback( $applied );
				return new WP_Error(
					'ml_prescription_line_update_failed',
					__( 'The prescription line could not be updated, so nothing was dispensed. Please try again.', 'ma-lumiere-clinic' )
				);
			}
			$total += $used;
		}

		ml_audit(
			'prescription_dispensed',
			'prescription',
			$prescription_id,
			sprintf(
				/* translators: 1: units, 2: prescription number. */
				__( 'Dispensed %1$d unit(s) for prescription %2$s.', 'ma-lumiere-clinic' ),
				$total,
				$prescription['prescription_number']
			)
		);

		return array(
			'dispensed' => $total,
			'items'     => count( $outstanding['items'] ),
			'movements' => $movements,
		);
	}

	/**
	 * Return dispensed units from a prescription back into their original
	 * batches. Only movements not already reversed are credited, so stock is
	 * never credited twice, and the reversal is recorded as a `return`
	 * movement pointing at the `dispense` row it undoes.
	 *
	 * @param int $prescription_id Prescription ID.
	 *
	 * @return array|WP_Error ['returned' => int, 'movements' => int]
	 */
	public static function return_from_prescription( $prescription_id ) {
		global $wpdb;

		$prescription_id = absint( $prescription_id );
		$prescription    = ML_Prescription_Repository::get( $prescription_id );
		if ( ! $prescription ) {
			return new WP_Error( 'ml_prescription_not_found', __( 'Prescription not found.', 'ma-lumiere-clinic' ) );
		}

		$dispensed_items = self::dispensed_items( $prescription_id );
		if ( empty( $dispensed_items ) ) {
			return array(
				'returned'  => 0,
				'movements' => 0,
			);
		}

		// Reverse the dispensing movements, most recent first, so the
		// original FEFO order is unwound.
		$outgoing = ML_Stock_Movement_Repository::for_prescription( $prescription_id );
		$returned = 0;
		$written  = 0;
		$pending  = 0;

		foreach ( array_reverse( $outgoing ) as $movement ) {
			if ( 'dispense' !== (string) $movement['movement_type'] ) {
				continue;
			}
			$batch_id = (int) $movement['batch_id'];
			$qty      = (int) $movement['quantity'];
			if ( ! $batch_id || 0 === $qty ) {
				continue;
			}

			// Skip a dispense that already has a matching reversal.
			$already = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM ' . ML_Database::table( 'stock_movements' ) . '
					 WHERE reference_type = %s AND reference_id = %d AND movement_type = %s',
					'stock_movement',
					(int) $movement['id'],
					'return'
				)
			);
			if ( $already > 0 ) {
				continue;
			}

			$batch = ML_Medicine_Batch_Repository::get( $batch_id );
			if ( ! $batch ) {
				++$pending;
				continue;
			}

			++$pending;
			$balance = ML_Medicine_Batch_Repository::apply_delta( $batch_id, -1 * $qty, (int) $batch['quantity_available'] );
			if ( is_wp_error( $balance ) ) {
				continue;
			}

			$written_move = ML_Stock_Movement_Repository::record(
				array(
					'medicine_id'    => (int) $movement['medicine_id'],
					'batch_id'       => $batch_id,
					'movement_type'  => 'return',
					'quantity'       => -1 * $qty,
					'balance_after'  => $balance,
					'unit_cost'      => (float) $movement['unit_cost'],
					'reference_type' => 'stock_movement',
					'reference_id'   => (int) $movement['id'],
					'notes'          => sprintf(
						/* translators: %d: dispense movement id. */
						__( 'Reversal of dispense movement #%d.', 'ma-lumiere-clinic' ),
						(int) $movement['id']
					),
				)
			);
			if ( is_wp_error( $written_move ) ) {
				ML_Medicine_Batch_Repository::apply_delta( $batch_id, $qty, $balance );
				continue;
			}

			++$written;
			$returned += abs( $qty );
		}

		/*
		 * Only clear the recorded dispense once every pending movement has
		 * been reversed. A partial reversal leaves dispensed_quantity intact so
		 * the remaining units can still be returned, and the reversal that
		 * already happened is skipped on the next attempt.
		 */
		if ( $written < $pending ) {
			ml_audit(
				'prescription_stock_return_partial',
				'prescription',
				$prescription_id,
				sprintf(
					/* translators: 1: returned units, 2: reversed movements, 3: pending movements. */
					__( 'Partially returned stock: %1$d unit(s) across %2$d of %3$d movement(s).', 'ma-lumiere-clinic' ),
					$returned,
					$written,
					$pending
				)
			);

			return new WP_Error(
				'ml_return_incomplete',
				sprintf(
					/* translators: 1: returned units, 2: pending movements. */
					__( 'Only %1$d unit(s) could be returned; %2$d movement(s) remain. Try again, or adjust the affected batch manually.', 'ma-lumiere-clinic' ),
					$returned,
					$pending - $written
				)
			);
		}

		// Zero the recorded dispense so the lines can be dispensed again.
		foreach ( $dispensed_items as $item ) {
			$wpdb->update(
				ML_Database::table( 'prescription_items' ),
				array( 'dispensed_quantity' => 0 ),
				array( 'id' => (int) $item['id'] ),
				array( '%d' ),
				array( '%d' )
			);
		}

		ml_audit(
			'prescription_stock_returned',
			'prescription',
			$prescription_id,
			sprintf(
				/* translators: %d: units. */
				__( 'Returned %d dispensed unit(s) to stock.', 'ma-lumiere-clinic' ),
				$returned
			)
		);

		return array(
			'returned'  => $returned,
			'movements' => $written,
		);
	}

	/**
	 * Prescription lines that still owe stock: linked to a medicine and not
	 * yet fully dispensed.
	 *
	 * @param int $prescription_id Prescription ID.
	 *
	 * @return array
	 */
	private static function outstanding_items( $prescription_id ) {
		$items = array();

		foreach ( self::stock_linked_items( $prescription_id ) as $item ) {
			$item['need'] = (int) $item['quantity'] - (int) $item['dispensed_quantity'];
			if ( $item['need'] > 0 ) {
				$items[] = $item;
			}
		}

		return array( 'items' => $items );
	}

	/**
	 * Prescription lines that have already had stock drawn against them.
	 *
	 * @param int $prescription_id Prescription ID.
	 *
	 * @return array
	 */
	private static function dispensed_items( $prescription_id ) {
		$items = array();

		foreach ( self::stock_linked_items( $prescription_id ) as $item ) {
			if ( (int) $item['dispensed_quantity'] > 0 ) {
				$items[] = $item;
			}
		}

		return $items;
	}

	/**
	 * Prescription lines linked to a catalogue medicine, in display order.
	 *
	 * @param int $prescription_id Prescription ID.
	 *
	 * @return array
	 */
	private static function stock_linked_items( $prescription_id ) {
		global $wpdb;

		$table = ML_Database::table( 'prescription_items' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				 WHERE prescription_id = %d
				   AND medicine_id IS NOT NULL
				   AND medicine_id > 0
				 ORDER BY sort_order ASC, id ASC",
				absint( $prescription_id )
			),
			ARRAY_A
		);

		$items = array();
		foreach ( (array) $rows as $row ) {
			$row['id']                 = (int) $row['id'];
			$row['medicine_id']        = (int) $row['medicine_id'];
			$row['quantity']           = (int) $row['quantity'];
			$row['dispensed_quantity'] = (int) $row['dispensed_quantity'];
			$items[]                   = $row;
		}
		return $items;
	}

	/**
	 * Undo a partially applied draw so a failed dispense leaves no trace in
	 * the batch quantities.
	 *
	 * The ledger is append-only, so each undone movement is cancelled with a
	 * matching `return` entry rather than being deleted. A cancellation is
	 * indistinguishable from a real patient return, so stock totals stay
	 * correct and the audit trail keeps every attempt.
	 *
	 * @param array $applied Applied entries with batch_id, quantity, movement_id.
	 *
	 * @return void
	 */
	private static function rollback( array $applied ) {
		foreach ( array_reverse( $applied ) as $entry ) {
			$batch = ML_Medicine_Batch_Repository::get( (int) $entry['batch_id'] );
			if ( ! $batch ) {
				continue;
			}
			$balance = ML_Medicine_Batch_Repository::apply_delta(
				(int) $entry['batch_id'],
				(int) $entry['quantity'],
				(int) $batch['quantity_available']
			);
			if ( is_wp_error( $balance ) ) {
				continue;
			}

			if ( empty( $entry['movement_id'] ) ) {
				// The original movement never landed, so the stock is simply
				// back where it started and there is nothing to cancel.
				continue;
			}

			ML_Stock_Movement_Repository::record(
				array(
					'medicine_id'    => (int) $batch['medicine_id'],
					'batch_id'       => (int) $entry['batch_id'],
					'movement_type'  => 'return',
					'quantity'       => (int) $entry['quantity'],
					'balance_after'  => $balance,
					'unit_cost'      => (float) $entry['unit_cost'],
					'reference_type' => 'stock_movement',
					'reference_id'   => (int) $entry['movement_id'],
					'notes'          => __( 'Dispensing cancelled; stock restored.', 'ma-lumiere-clinic' ),
				)
			);
		}
	}

	/**
	 * Replenishment + expiry alerts for the dashboard and the Alerts tab.
	 *
	 * @return array
	 */
	public static function alerts() {
		$out_of_stock = array();
		$low_stock    = array();

		foreach ( ML_Medicine_Repository::low_stock( 200 ) as $row ) {
			if ( 'out' === $row['stock_state'] ) {
				$out_of_stock[] = $row;
			} else {
				$low_stock[] = $row;
			}
		}

		$expired = ML_Medicine_Batch_Repository::list(
			array(
				'expiring_before' => current_time( 'Y-m-d', true ),
				'only_available'  => true,
				'per_page'        => 200,
			)
		);

		return array(
			'out_of_stock'  => $out_of_stock,
			'low_stock'     => $low_stock,
			'expiring_soon' => ML_Medicine_Batch_Repository::expiring(
				gmdate( 'Y-m-d', time() + ( self::EXPIRY_WINDOW_DAYS * DAY_IN_SECONDS ) ),
				200
			),
			'expired'       => $expired['items'],
		);
	}

	/**
	 * Stock position for one medicine, including its batches and history.
	 *
	 * @param int $medicine_id Medicine ID.
	 *
	 * @return array|null
	 */
	public static function stockcard( $medicine_id ) {
		$medicine = ML_Medicine_Repository::get( $medicine_id );
		if ( ! $medicine ) {
			return null;
		}

		$batches = ML_Medicine_Batch_Repository::for_medicine( $medicine_id );
		$history = ML_Stock_Movement_Repository::list(
			array(
				'medicine_id' => absint( $medicine_id ),
				'per_page'    => 50,
			)
		);

		return array(
			'medicine' => $medicine,
			'batches'  => $batches,
			'history'  => $history['items'],
			'value'    => array_sum( wp_list_pluck( $batches, 'stock_value' ) ),
		);
	}

	/**
	 * Receipt note assembled from the submitted purchase details.
	 *
	 * @param array $input Raw input.
	 * @param array $batch Saved batch row.
	 *
	 * @return string
	 */
	private static function receipt_note( array $input, $batch ) {
		$parts = array();

		if ( ! empty( $input['supplier'] ) ) {
			$parts[] = sprintf(
				/* translators: %s: supplier name. */
				__( 'Supplier: %s', 'ma-lumiere-clinic' ),
				sanitize_text_field( (string) $input['supplier'] )
			);
		}
		if ( ! empty( $input['batch_number'] ) ) {
			$parts[] = sprintf(
				/* translators: %s: batch number. */
				__( 'Batch: %s', 'ma-lumiere-clinic' ),
				sanitize_text_field( (string) $input['batch_number'] )
			);
		}
		if ( $batch && ! empty( $batch['expiry_date'] ) ) {
			$parts[] = sprintf(
				/* translators: %s: expiry date. */
				__( 'Expiry: %s', 'ma-lumiere-clinic' ),
				ml_date( $batch['expiry_date'] )
			);
		}

		return implode( ' — ', $parts );
	}
}
