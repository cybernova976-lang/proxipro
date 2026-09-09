@extends('admin.layouts.app')
@section('title', 'Studio e-mail')
@section('content')
<a href="{{ route('admin.emails.index') }}">← E-mails à valider</a>
<h1 class="h3 fw-bold mt-3">Studio e-mail Prokejem</h1>
<p class="text-muted">Composez votre message, ajoutez vos photos et choisissez vos destinataires.</p>
@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form action="{{ route('admin.emails.store') }}" method="POST" enctype="multipart/form-data" id="composer">
@csrf
<div class="row g-4">
<div class="col-xl-6">
<section class="card border-0 shadow-sm mb-3"><div class="card-body">
<h2 class="h5">1. Votre message</h2>
@foreach(['subject'=>'Objet du message', 'eyebrow'=>'Sur-titre (facultatif)', 'headline'=>'Grand titre'] as $name=>$label)
<label for="{{ $name }}" class="form-label mt-3">{{ $label }}</label><input id="{{ $name }}" name="{{ $name }}" class="form-control" maxlength="255" value="{{ old($name) }}" @if($name !== 'eyebrow') required @endif>
@endforeach
<label for="body" class="form-label mt-3">Texte du message</label><textarea id="body" name="body" rows="8" class="form-control" maxlength="5000" required>{{ old('body') }}</textarea>
<label for="cta_label" class="form-label mt-3">Texte du bouton (facultatif)</label><input id="cta_label" name="cta_label" class="form-control" maxlength="80" value="{{ old('cta_label') }}">
<label for="cta_url" class="form-label mt-3">Destination du bouton</label><input id="cta_url" name="cta_url" type="url" class="form-control" value="{{ old('cta_url') }}" placeholder="https://www.prokejem.fr/">
<label for="images" class="form-label mt-3">Photos — 3 maximum</label><input id="images" name="images[]" type="file" multiple accept="image/jpeg,image/png,image/webp" class="form-control">
<p class="small text-muted mt-2">JPEG, PNG ou WebP, 5 Mo par photo. Vous pouvez les sélectionner en plusieurs fois.</p>
<div id="photos" class="d-flex flex-wrap gap-2"></div><p id="photoError" class="text-danger" role="alert"></p>
</div></section>
<section class="card border-0 shadow-sm"><div class="card-body">
<h2 class="h5">2. Vos destinataires</h2>
<p class="small text-muted">{{ $recipients->count() }} comptes actifs avec une adresse vérifiée, inscrits à la newsletter et autorisant les e-mails.</p>
<input type="hidden" name="audience" id="audience" value="selected">
<div class="d-flex gap-2 mb-3"><button type="button" id="selectAll" class="btn btn-outline-primary">Tous les utilisateurs éligibles</button><button type="button" id="selectNone" class="btn btn-outline-secondary">Tout désélectionner</button></div>
<label for="recipientSearch" class="visually-hidden">Rechercher un destinataire</label><input id="recipientSearch" class="form-control mb-2" placeholder="Rechercher un nom ou une adresse">
<div style="max-height:280px;overflow:auto" id="recipientList">
@foreach($recipients as $recipient)
<label class="recipient d-flex gap-2 border-bottom py-2"><input type="checkbox" name="recipients[]" value="{{ $recipient->id }}" @checked(in_array($recipient->id, old('recipients', [])))><span>{{ $recipient->name }}<small class="d-block text-muted">{{ $recipient->email }}</small></span></label>
@endforeach
</div>
<p class="mt-3 fw-bold" id="selectionCount" aria-live="polite"></p>
<button class="btn btn-primary w-100" type="submit">Préparer les e-mails pour validation</button>
<p class="small text-muted mt-2 mb-0">Chaque destinataire reçoit un message individuel après votre validation dans la file d’envoi.</p>
</div></section>
</div>
<div class="col-xl-6"><section class="card border-0 shadow-sm"><div class="card-header bg-white d-flex justify-content-between align-items-center"><strong>Aperçu instantané</strong><button type="button" class="btn btn-sm btn-outline-secondary" id="mobilePreview">Vue mobile</button></div><div style="background:#f3f6fb;padding:12px;overflow:auto"><iframe id="preview" title="Aperçu du message" sandbox="" style="display:block;width:100%;height:800px;border:0;margin:auto;background:white"></iframe></div></section></div>
</div></form>
<script>
(() => {
 const form=document.getElementById('composer'), preview=document.getElementById('preview');
 const boxes=[...document.querySelectorAll('.recipient input')];
 const audience=document.getElementById('audience');
 const files=[]; let urls=[];
 const safe=value=>{const el=document.createElement('span');el.textContent=value;return el.innerHTML.replace(/"/g,'&quot;').replace(/'/g,'&#39;');};
 const value=id=>document.getElementById(id).value;
 function updatePreview(){
  const link=value('cta_url'); const valid=/^https?:\/\//i.test(link);
  preview.srcdoc=`<!doctype html><html lang="fr"><meta name="viewport" content="width=device-width,initial-scale=1"><body style="margin:0;background:#f3f6fb;font-family:Arial;color:#172033"><div style="max-width:620px;margin:20px auto;background:white;border-radius:20px;overflow:hidden"><div style="padding:24px;text-align:center;border-bottom:1px solid #e8eef8"><img src="${safe(@json(asset('images/brand/prokejem-logo.png')))}" alt="Prokejem" width="180"></div>${urls.map(url=>`<img src="${url}" alt="Illustration" style="width:100%;height:auto;display:block">`).join('')}<div style="padding:30px"><p style="color:#2563eb;text-transform:uppercase;font-size:12px">${safe(value('eyebrow'))}</p><h1 style="font-size:28px">${safe(value('headline')||'Votre prochain message')}</h1><p style="white-space:pre-wrap;line-height:1.65;color:#536176">${safe(value('body')||'Le contenu de votre e-mail apparaît ici.')}</p>${valid&&value('cta_label')?`<div style="text-align:center;padding:20px"><a href="${safe(link)}" style="display:inline-block;background:#2563eb;padding:17px 28px;color:white;border-radius:12px;text-decoration:none">${safe(value('cta_label'))} →</a></div>`:''}</div><div style="padding:20px;text-align:center;background:#f8fafc;font-size:12px">Prokejem · Gérer mes préférences e-mail</div></div></body></html>`;
 }
 function photos(){
  urls.forEach(URL.revokeObjectURL); urls=files.map(file=>URL.createObjectURL(file));
  const dt=new DataTransfer(); files.forEach(file=>dt.items.add(file));document.getElementById('images').files=dt.files;
  const container=document.getElementById('photos');container.replaceChildren();
  files.forEach((file,i)=>{const button=document.createElement('button');button.type='button';button.className='btn btn-outline-secondary';button.textContent=file.name+' ×';button.onclick=()=>{files.splice(i,1);photos();};container.append(button);});updatePreview();
 }
 document.getElementById('images').addEventListener('change',event=>{
  const incoming=[...event.target.files];document.getElementById('photoError').textContent='';
  for(const file of incoming){if(files.length>=3||file.size>5*1024*1024||!['image/jpeg','image/png','image/webp'].includes(file.type)){document.getElementById('photoError').textContent='Maximum 3 images de 5 Mo (JPEG, PNG, WebP).';continue;}files.push(file);}photos();
 });
 function count(){document.getElementById('selectionCount').textContent=boxes.filter(box=>box.checked).length+' destinataire(s) sélectionné(s)';}
 document.getElementById('selectAll').onclick=()=>{boxes.forEach(box=>box.checked=true);audience.value='all';count();};
 document.getElementById('selectNone').onclick=()=>{boxes.forEach(box=>box.checked=false);audience.value='selected';count();};
 boxes.forEach(box=>box.addEventListener('change',()=>{audience.value='selected';count();}));
 document.getElementById('recipientSearch').oninput=event=>{const query=event.target.value.toLocaleLowerCase();document.querySelectorAll('.recipient').forEach(row=>{row.classList.toggle('d-none',!row.textContent.toLocaleLowerCase().includes(query));});};
 document.getElementById('mobilePreview').onclick=()=>{preview.style.maxWidth=preview.style.maxWidth?'':'390px';};
 form.addEventListener('input',updatePreview); count(); updatePreview();
})();
</script>
@endsection
