import{c as Y,h as F,z as W,y as J,d as X,o as Z,e as u,f as g,w as i,g as o,k as a,Q as d,i as f,n as $,l as b,m as R,p as K,s as v,A as T,F as D,r as Q,j as y,t as m,B as ee,C as te,D as A,E as ae,G,H as k}from"./index--UOpw9SA.js";import{Q as ie,a as E,b as O}from"./QTable-DY4QVIoK.js";import{Q as oe,a as q,b as x}from"./QList-ikd-jaAE.js";import{Q as H}from"./QBanner-DG9EYBYn.js";import{Q as le}from"./QPage-hh6nbmsr.js";import{u as ne}from"./use-quasar-Dg9CMpiX.js";import{c as I}from"./copy-to-clipboard-DqMUbtGS.js";import"./QMarkupTable-oAUF9Q3o.js";import"./QSelect-DJoIIXgv.js";import"./QItemLabel-Cm7Roiho.js";const L=Y({name:"QTr",props:{props:Object,noHover:Boolean},setup(_,{slots:s}){const r=J(()=>"q-tr"+(_.props===void 0||_.props.header===!0?"":" "+_.props.__trClass)+(_.noHover===!0?" q-tr--no-hover":""));return()=>{var c;return F("tr",{style:(c=_.props)==null?void 0:c.__trStyle,class:r.value},W(s.default))}}}),se={class:"page-header"},re={class:"row items-center justify-between"},de={key:0,class:"empty-state"},ce={class:"row q-pa-md q-col-gutter-md"},ue={class:"col-12 col-md-8"},ye={class:"key-display",style:{"font-size":"11px","white-space":"pre-wrap","word-break":"break-all"}},pe={class:"col-12 col-md-4"},me={class:"row q-col-gutter-md"},_e={class:"col-12 col-md-6"},fe={class:"key-display"},ge={class:"col-12 col-md-6"},be={class:"key-display",style:{background:"#fef2f2","border-color":"#fecaca"}},De={__name:"GenerateKeysPage",setup(_){const s=ne(),r=X(),c=k(!1),h=k(!1),w=k("default"),C=k(!1),p=k(null),N=[{name:"id",label:"ID",field:"id",align:"left",sortable:!0},{name:"key_name",label:"Name",field:"key_name",align:"left"},{name:"created_at",label:"Created",field:"created_at",align:"left",sortable:!0},{name:"actions",label:"Actions",field:"id",align:"center"}];async function V(l){l&&(await I(l),s.notify({type:"positive",message:"Copied to clipboard!"}))}async function U(){var l,e;C.value=!0;try{const t=await r.generateKey(w.value||"default");p.value=t,c.value=!1,h.value=!0,s.notify({type:"positive",message:"Key pair generated successfully!"})}catch(t){s.notify({type:"negative",message:((e=(l=t.response)==null?void 0:l.data)==null?void 0:e.error)||"Failed to generate key pair"})}finally{C.value=!1}}function B(){var n;if(!((n=p.value)!=null&&n.private_key))return;const l=new Blob([p.value.private_key],{type:"application/x-pem-file"}),e=URL.createObjectURL(l),t=document.createElement("a");t.href=e,t.download=`${w.value||"private"}_key.pem`,t.click(),URL.revokeObjectURL(e),s.notify({type:"positive",message:"Private key downloaded!"})}async function j(){var l;(l=p.value)!=null&&l.public_key&&(await I(p.value.public_key),s.notify({type:"positive",message:"Public key copied to clipboard!"}))}function z(l){s.dialog({title:'<span class="text-negative"><q-icon name="warning" /> Critical Warning</span>',message:`Deleting this key will <b>permanently erase</b> the Private Key file (.pem) from the server.<br><br>Old licenses previously signed by <b>${l.key_name}</b> will become orphaned and cannot be re-issued or natively verified by the system.<br><br>Type <strong>DELETE</strong> below to confirm your irreversible action:`,html:!0,prompt:{model:"",type:"text",isValid:e=>e==="DELETE"},cancel:!0,persistent:!0,color:"negative"}).onOk(async()=>{try{await r.deleteKey(l.id),s.notify({type:"positive",message:"Private Key permanently deleted."})}catch{s.notify({type:"negative",message:"Failed to delete key"})}})}function M(l){const e=window.location.origin+"/wp-json/tslm/v1/licenses/heartbeat",t=`# TS License Manager Integration Guide

## 1. Quick Start

1. **Generate RSA Key Pair**: You have generated key **${l.key_name}**.
2. **Embed Public Key**: Copy the code from Section 3 into your plugin's code.
3. **Generate License**: Use the Generate License tab to create an activation code for your customer's domain.
4. **Customer Activates**: The customer pastes the code. Your plugin verifies it using the embedded public key – 100% offline initially!

## 2. API Server
**Heartbeat Endpoint:** \`${e}\`

## 3. Your Public Key (ID: ${l.id})
To verify licenses signed by **${l.key_name}**, you must hardcode the following Public Key into your plugin's source code.

\`\`\`
${l.public_key}
\`\`\`

## 4. Full Integration Code Snippet (Hybrid Heartbeat)
Add this master snippet to your plugin to verify signatures, check offline expiry, and automatically execute the 7-day heartbeat checks.

\`\`\`php
// In your plugin's license checker (HARDCODE THIS FOR SECURITY)
$public_key = <<<EOD
${l.public_key}
EOD;

$license_data = json_decode(
  base64_decode($activation_code), true
);

// Step 1: Verify signature
$signature = base64_decode($license_data['sig']);
$payload = json_encode($license_data['data']);
$valid = openssl_verify(
  $payload, $signature, $public_key,
  OPENSSL_ALGO_SHA256
);

if ($valid !== 1) {
  return 'invalid_signature';
}

// Step 2: Verify domain (skip on localhost)
$data = $license_data['data'];
$current = parse_url(home_url(), PHP_URL_HOST);
$is_local = in_array($current, [
  'localhost', '127.0.0.1', '::1'
]) || str_ends_with($current, '.local');

if (!$is_local && $data['domain'] !== $current) {
  return 'domain_mismatch';
}

// Step 3: Check expiry (lifetime = no expiry)
if (!empty($data['expires_at'])) {
  $expires = strtotime($data['expires_at']);
  if (time() > $expires) {
    return 'license_expired';
  }
}

// Step 4: Remote Lock (Hybrid Heartbeat)
// Run this via WP-Cron or Async to ensure fast page loads
if (!$is_local) {
  $last_check = (int) get_option('my_plugin_last_check', 0);
  
  // Every 7 days, verify with the license server
  if (time() - $last_check > 7 * DAY_IN_SECONDS) {
    $resp = wp_remote_post('${e}', [
       'body' => [
         'domain' => $current,
         'code'   => $activation_code
       ],
       'timeout' => 15
    ]);
    
    if (!is_wp_error($resp) && wp_remote_retrieve_response_code($resp) === 200) {
       $body = json_decode(wp_remote_retrieve_body($resp), true);
       if (!empty($body['data'])) {
           // Verify RSA Signature of Heartbeat
           $hb_sig = base64_decode($body['data']['sig']);
           $hb_data = json_encode($body['data']['data']);
           if (openssl_verify($hb_data, $hb_sig, $public_key, OPENSSL_ALGO_SHA256) === 1) {
               $hb_payload = $body['data']['data'];
               
               if ($hb_payload['status'] === 'locked') {
                   update_option('my_plugin_remote_locked', true);
                   return 'license_locked_remotely';
               }
               
               // Success! Reset check and clear grace
               update_option('my_plugin_last_check', time());
               delete_option('my_plugin_grace_start');
               delete_option('my_plugin_remote_locked');
           }
       }
    } else {
       // Server down/blocked. Start 3-day Grace Period (Deadman Switch)
       $grace = (int) get_option('my_plugin_grace_start', 0);
       if (!$grace) {
           update_option('my_plugin_grace_start', time());
       } elseif (time() - $grace > 3 * DAY_IN_SECONDS) {
           return 'grace_period_expired';
       }
    }
  }
  
  // Block if previously locked
  if (get_option('my_plugin_remote_locked')) return 'license_locked_remotely';
}

return 'active'; // ✅ License valid!
\`\`\`

## 5. Pro Tips & Security Best Practices

### The "Refresh License" Button
Since the heartbeat only checks every 7 days, if a customer pays to unlock their site, they will need a way to force an immediate check. In your plugin's settings page, add a **"Refresh License"** button that runs:
\`\`\`php
delete_option('my_plugin_last_check');
delete_option('my_plugin_remote_locked');
\`\`\`
...so the next page load will immediately contact the license server and unlock their site.

### Avoid Database Storage
**Do not store the public key in the database (wp_options)**: This prevents malicious users from easily replacing it with their own key to bypass verification. Hardcode it directly as shown in the PHP snippet.

### Code Obfuscation
Use a PHP obfuscator (like **ionCube**, SourceGuardian, or php-obfuscator) on the file containing the \`verify_ts_license\` function and the Public Key string. This makes it extremely difficult for hackers to locate and modify the verification logic.

---
*Generated by TS License Manager on ${new Date().toLocaleString()}*
`,n=new Blob([t],{type:"text/markdown;charset=utf-8"}),S=URL.createObjectURL(n),P=document.createElement("a");P.href=S,P.download=`integration-guide-key-${l.id}.md`,P.click(),URL.revokeObjectURL(S),s.notify({type:"positive",message:"Integration Guide exported!"})}return Z(()=>{r.fetchKeys()}),(l,e)=>(u(),g(le,{padding:""},{default:i(()=>[o("div",se,[o("div",re,[e[7]||(e[7]=o("div",null,[o("h1",null,"Key Management"),o("p",null,"Manage RSA-2048 key pairs for license signing")],-1)),a(d,{color:"primary",icon:"add",label:"Generate New Key Pair",onClick:e[0]||(e[0]=t=>c.value=!0),loading:f(r).loading},null,8,["loading"])])]),f(r).keys.length===0&&!f(r).loading?(u(),$("div",de,[a(b,{name:"vpn_key_off"}),e[8]||(e[8]=o("h3",null,"No Key Pair Generated",-1)),e[9]||(e[9]=o("p",null,"Generate an RSA-2048 key pair to start issuing licenses",-1)),a(d,{color:"primary",icon:"add",label:"Generate Key Pair",onClick:e[1]||(e[1]=t=>c.value=!0),class:"q-mt-md"})])):R("",!0),f(r).keys.length>0?(u(),g(K,{key:1,class:"q-mt-lg"},{default:i(()=>[a(v,null,{default:i(()=>[...e[10]||(e[10]=[o("div",{class:"text-h6"},"All Key Pairs",-1)])]),_:1}),a(ie,{rows:f(r).keys,columns:N,"row-key":"id",flat:"",loading:f(r).loading,"rows-per-page-options":[10,20,50]},{header:i(t=>[a(L,{props:t},{default:i(()=>[a(O,{"auto-width":""}),(u(!0),$(D,null,Q(t.cols,n=>(u(),g(O,{key:n.name,props:t},{default:i(()=>[y(m(n.label),1)]),_:2},1032,["props"]))),128))]),_:2},1032,["props"])]),body:i(t=>[a(L,{props:t,class:"cursor-pointer",onClick:n=>t.expand=!t.expand},{default:i(()=>[a(E,{"auto-width":""},{default:i(()=>[a(d,{size:"sm",color:"primary",round:"",dense:"",outline:"",onClick:T(n=>t.expand=!t.expand,["stop"]),icon:t.expand?"remove":"add"},null,8,["onClick","icon"])]),_:2},1024),(u(!0),$(D,null,Q(t.cols,n=>(u(),g(E,{key:n.name,props:t},{default:i(()=>[n.name==="actions"?(u(),g(d,{key:0,flat:"",round:"",size:"sm",icon:"delete",color:"negative",onClick:T(S=>z(t.row),["stop"])},null,8,["onClick"])):(u(),$(D,{key:1},[y(m(n.value),1)],64))]),_:2},1032,["props"]))),128))]),_:2},1032,["props","onClick"]),ee(a(L,{props:t},{default:i(()=>[a(E,{colspan:"100%",class:"bg-grey-1"},{default:i(()=>[o("div",ce,[o("div",ue,[e[11]||(e[11]=o("div",{class:"text-subtitle2 text-grey-8 q-mb-sm"},"Public Key",-1)),o("div",ye,m(t.row.public_key),1),a(d,{size:"sm",color:"primary",outline:"",icon:"content_copy",label:"Copy Public Key",onClick:n=>V(t.row.public_key),class:"q-mt-sm"},null,8,["onClick"]),a(d,{size:"sm",color:"secondary",outline:"",icon:"download",label:"Export Guide (.md)",onClick:n=>M(t.row),class:"q-mt-sm q-ml-sm"},null,8,["onClick"])]),o("div",pe,[e[14]||(e[14]=o("div",{class:"text-subtitle2 text-grey-8 q-mb-sm"},"Details",-1)),a(oe,{dense:""},{default:i(()=>[a(q,null,{default:i(()=>[a(x,null,{default:i(()=>[...e[12]||(e[12]=[y("Private Key Hash",-1)])]),_:1}),a(x,{side:"",class:"text-caption",style:{"font-family":"monospace"}},{default:i(()=>[y(m((t.row.private_key_hash||"").substring(0,16))+"... ",1)]),_:2},1024)]),_:2},1024),a(q,null,{default:i(()=>[a(x,null,{default:i(()=>[...e[13]||(e[13]=[y("Created By",-1)])]),_:1}),a(x,{side:""},{default:i(()=>[y(m(t.row.created_by),1)]),_:2},1024)]),_:2},1024)]),_:2},1024)])])]),_:2},1024)]),_:2},1032,["props"]),[[te,t.expand]])]),_:1},8,["rows","loading"])]),_:1})):R("",!0),a(A,{modelValue:c.value,"onUpdate:modelValue":e[4]||(e[4]=t=>c.value=t),persistent:""},{default:i(()=>[a(K,{style:{"min-width":"450px"}},{default:i(()=>[a(v,{class:"row items-center"},{default:i(()=>[a(b,{name:"vpn_key",color:"primary",size:"md",class:"q-mr-sm"}),e[15]||(e[15]=o("div",{class:"text-h6"},"Generate New Key Pair",-1))]),_:1}),a(v,null,{default:i(()=>[a(H,{class:"bg-warning text-dark q-mb-md rounded-borders",dense:""},{avatar:i(()=>[a(b,{name:"warning"})]),default:i(()=>[e[16]||(e[16]=y(" You are generating a new RSA key pair. Old keys will remain active and you can choose which key to sign new licenses with later. ",-1))]),_:1}),a(ae,{modelValue:w.value,"onUpdate:modelValue":e[2]||(e[2]=t=>w.value=t),label:"Key Name",hint:"A friendly name for this key pair",outlined:"",class:"q-mb-md"},null,8,["modelValue"])]),_:1}),a(G,{align:"right"},{default:i(()=>[a(d,{flat:"",label:"Cancel",onClick:e[3]||(e[3]=t=>c.value=!1)}),a(d,{color:"primary",label:"Generate",icon:"vpn_key",onClick:U,loading:C.value},null,8,["loading"])]),_:1})]),_:1})]),_:1},8,["modelValue"]),a(A,{modelValue:h.value,"onUpdate:modelValue":e[6]||(e[6]=t=>h.value=t),persistent:""},{default:i(()=>[a(K,{style:{"min-width":"800px","max-width":"95vw"}},{default:i(()=>[a(v,{class:"row items-center bg-positive text-white"},{default:i(()=>[a(b,{name:"check_circle",size:"md",class:"q-mr-sm"}),e[17]||(e[17]=o("div",{class:"text-h6"},"Key Pair Generated Successfully!",-1))]),_:1}),a(v,null,{default:i(()=>{var t,n;return[a(H,{class:"bg-red-1 text-red-9 q-mb-md rounded-borders",dense:""},{avatar:i(()=>[a(b,{name:"error",color:"red"})]),default:i(()=>[e[18]||(e[18]=o("strong",null,"IMPORTANT:",-1)),e[19]||(e[19]=y(" Download the private key NOW. It cannot be retrieved later! ",-1))]),_:1}),o("div",me,[o("div",_e,[e[20]||(e[20]=o("div",{class:"text-subtitle2 q-mb-xs"},"Public Key",-1)),o("div",fe,m((t=p.value)==null?void 0:t.public_key),1)]),o("div",ge,[e[21]||(e[21]=o("div",{class:"text-subtitle2 q-mb-xs"},"Private Key (KEEP SECRET!)",-1)),o("div",be,m((n=p.value)==null?void 0:n.private_key),1)])])]}),_:1}),a(G,{align:"right"},{default:i(()=>[a(d,{color:"primary",icon:"download",label:"Download Private Key",onClick:B}),a(d,{color:"secondary",icon:"content_copy",label:"Copy Public Key",onClick:j}),a(d,{flat:"",label:"Close",onClick:e[5]||(e[5]=t=>h.value=!1)})]),_:1})]),_:1})]),_:1},8,["modelValue"])]),_:1}))}};export{De as default};
