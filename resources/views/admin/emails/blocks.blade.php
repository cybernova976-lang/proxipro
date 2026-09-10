@php
    $emailForEditor = $managedEmail ?? null;
    $existingPaths = $emailForEditor?->image_paths ?? [];
    $initialBlocks = $emailForEditor?->metadata['content_blocks'] ?? null;
    if (!$initialBlocks) {
        $initialBlocks = collect($existingPaths)->map(fn($path) => ['type'=>'image','path'=>$path,'width'=>560,'align'=>'center','caption'=>''])->all();
        $initialBlocks[] = ['type'=>'text', 'text'=>old('body', $emailForEditor?->body ?? '')];
    }
    $initialBlocks = array_map(function($block) use ($existingPaths) {
        if ($block['type'] === 'image') {
            $block['ref'] = 'existing:'.array_search($block['path'], $existingPaths, true);
            unset($block['path']);
        }
        return $block;
    }, $initialBlocks);
    $savedLayout = old('content_layout');
    if (is_string($savedLayout) && is_array(json_decode($savedLayout, true))) $initialBlocks = json_decode($savedLayout, true);
    $editorImages = array_map(fn($path) => storage_url($path), $existingPaths);
@endphp
<section id="email-block-editor" class="mt-3" aria-label="Mise en page du message">
    <h3 class="h6 fw-bold">Composez votre message par blocs</h3>
    <p class="small text-muted">Ajoutez du texte et jusqu’à 3 photos. Les flèches déplacent chaque bloc au début, au milieu ou à la fin. La taille des photos s’adapte aux petits écrans sans les déformer.</p>
    <input type="hidden" name="content_layout" id="content-layout">
    <input type="hidden" name="body" id="body">
    <div id="email-blocks" class="d-grid gap-3"></div>
    <div class="d-flex flex-wrap gap-2 mt-3">
        <button type="button" class="btn btn-outline-primary" id="add-text-block">+ Ajouter du texte</button>
        <label for="block-images" class="form-label w-100 mb-0">Ajouter une ou plusieurs photos</label>
        <input id="block-images" class="form-control" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp">
    </div>
    <p class="small text-muted mt-2">JPEG, PNG ou WebP · 5 Mo par photo · 5 000 caractères de texte au total.</p>
    <p id="block-error" class="text-danger small" role="alert"></p>
    <noscript><div class="alert alert-warning">Activez JavaScript pour composer le message.</div></noscript>
</section>
<script>
(() => {
    const root = document.getElementById('email-block-editor');
    const form = root.closest('form'), list = document.getElementById('email-blocks');
    const input = document.getElementById('block-images'), error = document.getElementById('block-error');
    const existing = @json($editorImages);
    const initial = @json($initialBlocks);
    const blocks = initial.filter(b => ['text','image'].includes(b.type)).map(b => ({...b,
        url: b.type === 'image' && /^existing:\d+$/.test(b.ref || '') ? existing[Number(b.ref.split(':')[1])] : null
    }));
    let frameRequested = false, initialized = false;
    const safe = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const field = name => form.elements.namedItem(name)?.value || '';
    function sync() {
        const transfer = new DataTransfer();
        const layout = blocks.map(b => {
            if (b.type === 'text') return {type:'text',text:b.text || ''};
            let ref = b.ref;
            if (b.file) {ref = 'new:'+transfer.items.length; transfer.items.add(b.file);}
            return {type:'image',ref,width:Number(b.width)||560,align:b.align||'center',caption:b.caption||''};
        });
        input.files = transfer.files;
        document.getElementById('content-layout').value = JSON.stringify(layout);
        document.getElementById('body').value = blocks.filter(b=>b.type==='text').map(b=>b.text||'').join('\n\n');
        if (!frameRequested) { frameRequested=true; requestAnimationFrame(()=>{frameRequested=false; preview();}); }
    }
    function preview() {
        const target = document.getElementById('preview') || document.getElementById('block-preview');
        if (!target) return;
        const html = blocks.map(b => b.type === 'text'
            ? `<div style="white-space:pre-wrap;overflow-wrap:anywhere;line-height:1.65;margin:0 0 20px;color:#536176">${safe(b.text)}</div>`
            : `<div style="text-align:${['left','right'].includes(b.align)?b.align:'center'};margin:0 0 20px">${b.url?`<img src="${safe(b.url)}" alt="${safe(b.caption||'Illustration')}" width="${Number(b.width)||560}" style="display:inline-block;width:${Number(b.width)||560}px;max-width:100%;height:auto;border-radius:8px">`:'<p>Photo à sélectionner à nouveau</p>'}${b.caption?`<div style="font-size:12px;color:#64748b;margin-top:6px">${safe(b.caption)}</div>`:''}</div>`).join('');
        const href=field('cta_url');
        target.srcdoc=`<!doctype html><html lang="fr"><meta name="viewport" content="width=device-width,initial-scale=1"><body style="margin:0;background:#f3f6fb;font-family:Arial;color:#172033"><div style="max-width:620px;margin:20px auto;background:white;border-radius:20px;overflow:hidden"><div style="padding:24px;text-align:center;border-bottom:1px solid #e8eef8"><img src="${safe(@json(asset('images/brand/prokejem-logo.png')))}" alt="Prokejem" width="180" style="max-width:100%;height:auto"></div><div style="padding:30px"><p style="color:#2563eb;font-size:12px;text-transform:uppercase">${safe(field('eyebrow'))}</p><h1 style="font-size:28px;overflow-wrap:anywhere">${safe(field('headline')||'Votre prochain message')}</h1>${html}${/^https?:\/\//i.test(href)&&field('cta_label')?`<p style="text-align:center"><a href="${safe(href)}" style="display:inline-block;padding:16px 24px;background:#2563eb;color:white;border-radius:10px;text-decoration:none">${safe(field('cta_label'))}</a></p>`:''}</div><div style="text-align:center;padding:20px;font-size:12px;background:#f8fafc">Prokejem · Gérer mes préférences e-mail</div></div></body></html>`;
    }
    function button(text, label, action, disabled=false) {
        const el=document.createElement('button'); el.type='button'; el.className='btn btn-sm btn-outline-secondary';
        el.textContent=text; el.setAttribute('aria-label',label); el.disabled=disabled; el.onclick=action; return el;
    }
    function render() {
        list.replaceChildren();
        blocks.forEach((b,i)=>{
            const card=document.createElement('div'); card.className='border rounded-3 p-3 bg-light'; card.style.minWidth='0';
            const bar=document.createElement('div'); bar.className='d-flex flex-wrap align-items-center gap-2 mb-2';
            const title=document.createElement('strong'); title.className='me-auto small'; title.textContent=`${i+1}. ${b.type==='text'?'Texte':'Photo'}`; bar.append(title);
            bar.append(button('↑',`Monter le bloc ${i+1}`,()=>{[blocks[i-1],blocks[i]]=[blocks[i],blocks[i-1]];render();},i===0));
            bar.append(button('↓',`Descendre le bloc ${i+1}`,()=>{[blocks[i],blocks[i+1]]=[blocks[i+1],blocks[i]];render();},i===blocks.length-1));
            bar.append(button('Retirer',`Retirer le bloc ${i+1}`,()=>{blocks.splice(i,1);render();})); card.append(bar);
            if(b.type==='text') {
                const text=document.createElement('textarea'); text.className='form-control'; text.rows=4; text.maxLength=5000;
                text.setAttribute('aria-label',`Texte du bloc ${i+1}`); text.value=b.text||'';
                text.oninput=()=>{b.text=text.value;sync();}; card.append(text);
            } else {
                if(b.url){const img=document.createElement('img');img.src=b.url;img.alt='Photo du bloc';img.style.cssText='max-width:100%;height:100px;object-fit:contain;display:block;margin-bottom:12px';card.append(img);}
                else {const note=document.createElement('p');note.textContent='Photo non conservée après une erreur : retirez ce bloc puis choisissez le fichier à nouveau.';note.className='text-danger small';card.append(note);}
                const sizeLabel=document.createElement('label'); sizeLabel.className='form-label small';sizeLabel.htmlFor='size-'+i;
                sizeLabel.textContent=`Largeur : ${b.width||560} px`;card.append(sizeLabel);
                const range=document.createElement('input');range.id='size-'+i;range.type='range';range.min=80;range.max=560;range.step=10;range.value=b.width||560;range.className='form-range';
                range.oninput=()=>{b.width=Number(range.value);sizeLabel.textContent=`Largeur : ${b.width} px`;sync();};card.append(range);
                const presets=document.createElement('div');presets.className='d-flex flex-wrap gap-1 mb-2';
                [[80,'Identité'],[160,'Petite'],[320,'Moyenne'],[560,'Pleine largeur']].forEach(([width,label])=>presets.append(button(label,`${label} pour la photo ${i+1}`,()=>{range.value=width;range.dispatchEvent(new Event('input',{bubbles:true}));})));card.append(presets);
                const align=document.createElement('select');align.className='form-select form-select-sm mb-2';align.setAttribute('aria-label',`Alignement de la photo ${i+1}`);
                [['left','À gauche'],['center','Centrée'],['right','À droite']].forEach(([value,label])=>{const o=document.createElement('option');o.value=value;o.textContent=label;align.append(o);});align.value=b.align||'center';align.onchange=()=>{b.align=align.value;sync();};card.append(align);
                const caption=document.createElement('input');caption.className='form-control form-control-sm';caption.placeholder='Légende (facultatif)';caption.setAttribute('aria-label',`Légende de la photo ${i+1}`);caption.maxLength=200;caption.value=b.caption||'';caption.oninput=()=>{b.caption=caption.value;sync();};card.append(caption);
            }
            list.append(card);
        }); sync();
        if (initialized) form.dispatchEvent(new Event('change', {bubbles:true}));
        initialized = true;
    }
    document.getElementById('add-text-block').onclick=()=>{if(blocks.length>=30){error.textContent='Maximum 30 blocs.';return;}blocks.push({type:'text',text:''});render();};
    input.onchange=()=>{
        error.textContent='';
        for(const file of [...input.files]) {
            if(blocks.filter(b=>b.type==='image').length>=3 || blocks.length>=30 || file.size>5*1024*1024 || !['image/jpeg','image/png','image/webp'].includes(file.type)){error.textContent='Maximum 3 photos de 5 Mo (JPEG, PNG, WebP).';continue;}
            blocks.push({type:'image',file,url:URL.createObjectURL(file),width:560,align:'center',caption:''});
        } render();
    };
    form.addEventListener('input',event=>{if(event.target.type!=='file'&&!root.contains(event.target))sync();});
    form.addEventListener('submit',event=>{
        sync();const body=document.getElementById('body').value.trim();
        if(!body||body.length>5000||blocks.some(b=>b.type==='image'&&!b.url)){
            event.preventDefault();error.textContent='Vérifiez les photos et ajoutez du texte (5 000 caractères maximum au total).';error.scrollIntoView({block:'center'});
        }
    });
    document.addEventListener('DOMContentLoaded',()=>{render();});
})();
</script>
