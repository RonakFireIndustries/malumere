import json, re, sys, glob, os
from bs4 import BeautifulSoup

files = sorted(glob.glob(sys.argv[1]))
for path in files:
    with open(path, encoding='utf-8', errors='replace') as f:
        html = f.read()
    soup = BeautifulSoup(html, 'html.parser')
    name = os.path.basename(path)
    print('\n' + '='*100)
    print('FILE:', name)
    # JSON-LD
    blocks = soup.find_all('script', type=re.compile('ld\+json', re.I))
    print('JSONLD blocks:', len(blocks))
    for i, b in enumerate(blocks):
        try:
            data = json.loads(b.string or b.get_text())
        except Exception as e:
            print('  invalid:', e)
            continue
        def walk(node, depth=0):
            if isinstance(node, list):
                for x in node: walk(x, depth)
            elif isinstance(node, dict):
                t = node.get('@type')
                print('  ' + '  '*depth + 'TYPE:', t, '| keys:', sorted(node.keys()))
                for k, v in node.items():
                    if k in ('@graph','mainEntity','itemListElement','hasPart','makesOffer','address','author','publisher','performer','geo','openingHoursSpecification','sameAs','knowsAbout','about','mentions','review','aggregateRating','hasOfferCatalog','areaServed','availableService','subjectOf','citation','reference'):
                        if isinstance(v, (dict, list)):
                            print('  ' + '  '*(depth+1) + k + ':')
                            walk(v, depth+2)
                        else:
                            print('  ' + '  '*(depth+1) + k + ': ' + str(v)[:200])
        walk(data)
    # headings
    print('HEADINGS:')
    for h in soup.find_all(re.compile('^h[1-6]$')):
        txt = ' '.join(h.get_text(' ', strip=True).split())
        print(' ', h.name, '|', txt[:180])
    # main text
    main = soup.find('main') or soup.find('article') or soup.body
    text = ' '.join(main.get_text(' ', strip=True).split()) if main else ''
    print('MAIN TEXT (first 3000):')
    print(text[:3000])
    # link anchors
    print('LINKS (internal, first 80):')
    seen=set()
    for a in soup.find_all('a', href=True):
        href=a['href']
        if href.startswith('http') and 'ma-lumiere.in' not in href: continue
        anchor=' '.join(a.get_text(' ', strip=True).split())
        key=(href,anchor)
        if key in seen: continue
        seen.add(key)
        print(' ', anchor[:80], '->', href[:100])
        if len(seen)>80: break
    # signals
    low = text.lower()
    signals = ['rishita','ray','qualification','mbbs','md ','dermatolog','experience','years','certif','medical','review','before','after','consent','source','study','journal','published','reference','citation','doctor','consultant','specialist','registered','licence','license','mci','nmc','fda','who ','evidence','research','acne','scar','pigment','hair','wedding','injectable','laser','mira','bhayander','churchgate','dahanu','mumbai','faq','appointment','book']
    print('SIGNAL COUNTS:', {s: low.count(s) for s in signals if low.count(s)>0})
