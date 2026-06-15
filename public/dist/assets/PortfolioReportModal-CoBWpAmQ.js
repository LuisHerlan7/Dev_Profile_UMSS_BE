import{_ as G}from"./pdfDoc-BgjfEwTV.js";import{u as se,b as ae,m as ie,a as de,c as ce,j as o,X as ne,F as V,I as le,D as pe,r as me}from"./index-BXuiTPFy.js";import{b as m}from"./react-DGP2VTlM.js";function r(s){return s.replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;").replaceAll('"',"&quot;").replaceAll("'","&#39;")}function fe(s,a){const d=window.URL.createObjectURL(s),e=document.createElement("a");e.href=d,e.download=a,e.click(),window.URL.revokeObjectURL(d)}function ge(s,a){return`<!DOCTYPE html>
  <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" lang="es">
    <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <title>${r(s)}</title>
      <style>
        @page { size: A4; margin: 18mm; }
        body { margin: 0; padding: 0; background: #edf3ff; color: #162033; font-family: Arial, Helvetica, sans-serif; }
        .doc-page { max-width: 900px; margin: 0 auto; padding: 28px 20px; }
        .doc-sheet { background: #ffffff; border: 1px solid #dbe5ff; border-radius: 28px; overflow: hidden; box-shadow: 0 24px 52px rgba(26,35,66,0.12); }
        .doc-ribbon { height: 16px; background: linear-gradient(90deg, #5b63ff 0%, #5048e5 42%, #08a5e8 100%); }
        .doc-header { padding: 28px; background: linear-gradient(135deg, #f8faff 0%, #eef3ff 100%); }
        .doc-hero { width: 100%; border-spacing: 0; }
        .doc-avatar-cell { width: 158px; vertical-align: top; padding-right: 20px; }
        .doc-avatar { width: 132px; height: 132px; border-radius: 28px; overflow: hidden; background: linear-gradient(135deg, #5048e5, #6c63ff); text-align: center; line-height: 132px; color: #fff; font-size: 42px; font-weight: 800; box-shadow: 0 16px 32px rgba(80,72,229,0.25); }
        .doc-avatar img { width: 132px; height: 132px; object-fit: cover; display: block; }
        .doc-pill { display: inline-block; padding: 7px 12px; border-radius: 999px; background: #eef2ff; color: #4f46e5; border: 1px solid #d8defe; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.14em; }
        .doc-name { margin: 16px 0 6px; font-size: 35px; font-weight: 800; color: #0f172a; line-height: 1.1; }
        .doc-role { margin: 0; font-size: 18px; font-weight: 700; color: #42526d; }
        .doc-summary { margin: 14px 0 0; font-size: 14px; line-height: 1.75; color: #4b5563; }
        .doc-main { padding: 28px; }
        .doc-grid { width: 100%; border-spacing: 0; margin-bottom: 22px; }
        .doc-grid td { width: 50%; vertical-align: top; }
        .doc-grid td:first-child { padding-right: 10px; }
        .doc-grid td:last-child { padding-left: 10px; }
        .doc-card { border: 1px solid #e1e8ff; border-radius: 22px; background: #f9fbff; padding: 18px; }
        .doc-card-title { margin: 0 0 10px; font-size: 14px; font-weight: 800; color: #0f172a; }
        .doc-meta { margin: 7px 0 0; font-size: 13px; line-height: 1.6; color: #475569; }
        .doc-section { margin-top: 24px; }
        .doc-section-title { margin: 0 0 14px; font-size: 20px; font-weight: 800; color: #0f172a; }
        .doc-tags { margin-top: 12px; }
        .doc-tag { display: inline-block; margin: 0 8px 8px 0; padding: 7px 11px; border-radius: 999px; background: #eef2ff; border: 1px solid #d9e3ff; color: #3730a3; font-size: 12px; font-weight: 700; }
        .doc-skills { width: 100%; border-spacing: 0; }
        .doc-skills td { width: 50%; vertical-align: top; padding: 0 8px 12px 0; }
        .doc-skill { border: 1px solid #e3e9ff; border-radius: 18px; padding: 16px; background: #fff; }
        .doc-skill-name { font-size: 15px; font-weight: 800; color: #0f172a; }
        .doc-skill-meta { margin-top: 8px; font-size: 12px; color: #64748b; }
        .doc-skill-progress { float: right; font-weight: 700; color: #4f46e5; }
        .doc-bar { margin-top: 10px; height: 10px; border-radius: 999px; background: #e5eafc; overflow: hidden; }
        .doc-bar-fill { height: 10px; border-radius: 999px; background: linear-gradient(90deg, #5b63ff 0%, #5048e5 42%, #08a5e8 100%); }
        .doc-list { margin-top: 4px; }
        .doc-item { border: 1px solid #e2e8ff; border-radius: 18px; background: #fff; padding: 16px; margin-bottom: 12px; }
        .doc-item-label { display: inline-block; padding: 5px 9px; border-radius: 999px; background: #eef2ff; color: #4f46e5; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }
        .doc-item-title { margin: 10px 0 6px; font-size: 16px; font-weight: 800; color: #0f172a; }
        .doc-item-subtitle { margin: 0; font-size: 13px; color: #64748b; }
        .doc-item-text { margin: 10px 0 0; font-size: 13px; line-height: 1.65; color: #475569; }
        .doc-footer { margin-top: 24px; padding-top: 16px; border-top: 1px solid #e3e8f8; font-size: 12px; color: #64748b; text-align: center; }
      </style>
    </head>
    <body>
      <div class="doc-page">${a}</div>
    </body>
  </html>`}function he(s,a,d,e){const b=s.name.split(" ").filter(Boolean).slice(0,2).map(i=>i[0]).join("").toUpperCase(),h=i=>i.length>0?`<table class="doc-skills">${i.map((x,v)=>`${v%2===0?'<tr class="doc-skill-row">':""}<td>
                <div class="doc-skill">
                  <div class="doc-skill-name">${r(x.name)}</div>
                  <div class="doc-skill-meta">
                    ${r(x.level||a.notConfigured)}
                    <span class="doc-skill-progress">${x.progress}%</span>
                  </div>
                  <div class="doc-bar"><div class="doc-bar-fill" style="width:${Math.max(0,Math.min(100,x.progress))}%"></div></div>
                </div>
              </td>${v%2===1||v===i.length-1?"</tr>":""}`).join("")}</table>`:`<div class="doc-item"><p class="doc-item-text">${r(a.emptySkills)}</p></div>`,C=s.records.length?s.records.map(i=>`
            <div class="doc-item">
              <span class="doc-item-label">${r(i.recordType)}</span>
              <p class="doc-item-title">${r(i.title)}</p>
              <p class="doc-item-subtitle">${r(i.footer)}</p>
              <p class="doc-item-text">${r(i.description||a.emptyDescription)}</p>
            </div>
          `).join(""):`<div class="doc-item"><p class="doc-item-text">${r(a.emptyExperience)}</p></div>`,E=s.projects.length?s.projects.map(i=>`
            <div class="doc-item">
              <span class="doc-item-label">${r(i.status||a.projectLabel)}</span>
              <p class="doc-item-title">${r(i.title)}</p>
              <p class="doc-item-subtitle">${r(i.subtitle)}</p>
              ${i.tags.length?`<div class="doc-tags">${i.tags.map(x=>`<span class="doc-tag">${r(x)}</span>`).join("")}</div>`:""}
            </div>
          `).join(""):`<div class="doc-item"><p class="doc-item-text">${r(a.emptyProjects)}</p></div>`;return`
    <div class="doc-sheet">
      <div class="doc-ribbon"></div>
      <div class="doc-header">
        <table class="doc-hero">
          <tr>
            <td class="doc-avatar-cell">
              <div class="doc-avatar">
                ${d?`<img src="${d}" alt="${r(s.name)}" />`:r(b||"DP")}
              </div>
            </td>
            <td valign="top">
              <span class="doc-pill">${r(a.profile)}</span>
              <p class="doc-name">${r(s.name)}</p>
              <p class="doc-role">${r(s.role||a.notConfigured)}</p>
              <p class="doc-summary">${r(s.summary||a.emptySummary)}</p>
            </td>
          </tr>
        </table>
      </div>

      <div class="doc-main">
        ${e.contact||e.trajectory?`<table class="doc-grid">
          <tr>
            ${e.contact?`<td>
              <div class="doc-card">
                <p class="doc-card-title">${r(a.contact)}</p>
                <p class="doc-meta"><strong>${r(a.email)}:</strong> ${r(s.contactEmail||a.notConfigured)}</p>
                <p class="doc-meta"><strong>${r(a.phone)}:</strong> ${r(s.phone||a.notConfigured)}</p>
                <p class="doc-meta"><strong>GitHub:</strong> ${r(s.github||a.notConfigured)}</p>
                <p class="doc-meta"><strong>LinkedIn:</strong> ${r(s.linkedin||a.notConfigured)}</p>
                <p class="doc-meta"><strong>${r(a.website)}:</strong> ${r(s.website||a.notConfigured)}</p>
              </div>
            </td>`:""}
            ${e.trajectory?`<td>
              <div class="doc-card">
                <p class="doc-card-title">${r(a.trajectory)}</p>
                <div class="doc-tags">
                  ${s.titleHierarchy.map(i=>`<span class="doc-tag">${r(i)}</span>`).join("")}
                  ${s.roleHierarchy.map(i=>`<span class="doc-tag">${r(i)}</span>`).join("")}
                </div>
              </div>
            </td>`:""}
          </tr>
        </table>`:""}

        ${e.technicalSkills?`<div class="doc-section">
          <p class="doc-section-title">${r(a.technicalSkills)}</p>
          ${h(s.technicalSkills)}
        </div>`:""}

        ${e.softSkills?`<div class="doc-section">
          <p class="doc-section-title">${r(a.softSkills)}</p>
          ${h(s.softSkills)}
        </div>`:""}

        ${e.projects?`<div class="doc-section">
          <p class="doc-section-title">${r(a.projects)}</p>
          <div class="doc-list">${E}</div>
        </div>`:""}

        ${e.experience?`<div class="doc-section">
          <p class="doc-section-title">${r(a.experience)}</p>
          <div class="doc-list">${C}</div>
        </div>`:""}

        <div class="doc-footer">${r(a.footer)}</div>
      </div>
    </div>
  `}async function xe(s){const d=await(await fetch(s)).blob();return await new Promise((e,b)=>{const h=new FileReader;h.onloadend=()=>e(String(h.result)),h.onerror=b,h.readAsDataURL(d)})}function we({open:s,onClose:a,dashboardData:d}){const{t:e}=se(),[b,h]=m.useState("pdf"),[C,E]=m.useState(!1),[i,x]=m.useState(!1),[v,X]=m.useState({contact:!0,trajectory:!0,technicalSkills:!0,softSkills:!0,projects:!0,experience:!0}),[D,U]=m.useState(null),[J,I]=m.useState(!1),L=m.useRef(null);m.useEffect(()=>{if(!s)return;const t=document.body.style.overflow;return document.body.style.overflow="hidden",()=>{document.body.style.overflow=t}},[s]);const c=m.useMemo(()=>{var g,u,H,z,y,k,A,S;if(!d)return null;const t=ae(d),n=ie(d.habilidades||[]),w=de(d.proyectos||[]),N=ce(d.experiencias||[],d.formaciones||[]);return{name:((u=(g=d.usuario)==null?void 0:g.nombre_completo)==null?void 0:u.toString())||((H=d.auth_user)==null?void 0:H.name)||"Developer",role:((y=(z=d.usuario)==null?void 0:z.profesion)==null?void 0:y.toString())||((k=d.auth_user)==null?void 0:k.role)||"Developer",summary:t.bio||"",contactEmail:t.contactEmail||"",phone:t.phone||"",github:t.github||"",linkedin:t.linkedin||"",website:t.website||"",titleHierarchy:t.titleHierarchy,roleHierarchy:t.roleHierarchy,technicalSkills:n.technical,softSkills:n.soft,projects:w.map(l=>({id:l.id,title:l.title,subtitle:l.subtitle,tags:l.tags,status:l.status})),records:N.map(l=>({id:l.id,title:l.title,description:l.description,footer:l.footer,recordType:l.recordType})),avatarUrl:((S=(A=d.usuario)==null?void 0:A.fotografiaUrl)==null?void 0:S.toString())||null}},[d]);m.useEffect(()=>{let t=!0;I(!1);async function n(){if(!s||!(c!=null&&c.avatarUrl)){U(null);return}try{const w=await xe(c.avatarUrl);t&&U(w)}catch{t&&U(null)}}return n(),()=>{t=!1}},[s,c==null?void 0:c.avatarUrl]);const B=m.useMemo(()=>({profile:e("dashboard.report.profile"),contact:e("dashboard.report.contact"),technicalSkills:e("dashboard.report.technicalSkills"),softSkills:e("dashboard.report.softSkills"),projects:e("dashboard.report.projects"),experience:e("dashboard.report.experience"),trajectory:e("dashboard.overview.trajectorySummary"),email:e("common.email"),phone:e("common.phone"),website:e("common.website"),emptySkills:e("dashboard.report.emptySkills"),emptyProjects:e("dashboard.report.emptyProjects"),emptyExperience:e("dashboard.report.emptyExperience"),emptySummary:e("dashboard.overview.notConfiguredYet"),notConfigured:e("dashboard.overview.notConfiguredYet"),emptyDescription:e("dashboard.report.emptyExperience"),projectLabel:e("dashboard.report.projects"),footer:e("dashboard.report.footer")}),[e]),P=m.useMemo(()=>e("dashboard.report.generatedName",{name:((c==null?void 0:c.name)||"Developer").replace(/\s+/g,"-")}),[c==null?void 0:c.name,e]),K=m.useMemo(()=>c?he(c,B,D,v):"",[D,v,B,c]);if(!s||!c)return null;const O=ge(P,K),Q=()=>{const t=new Blob(["\uFEFF",O],{type:"application/msword"});fe(t,`${P}.doc`)},Z=async()=>{var q;const t=(q=L.current)==null?void 0:q.contentDocument;if(!t)throw new Error("No se pudo preparar la vista del reporte.");const n=t.querySelector(".doc-page");if(!n)throw new Error("No se pudo preparar la vista del reporte.");const[{default:w},{jsPDF:N}]=await Promise.all([G(()=>import("./pdfCanvas-QH1iLAAe.js"),[]),G(()=>import("./pdfDoc-BgjfEwTV.js").then(p=>p.j),[])]),g=await w(n,{backgroundColor:"#edf3ff",scale:2,useCORS:!0,windowWidth:n.scrollWidth,windowHeight:n.scrollHeight}),u=new N("p","pt","a4"),H=u.internal.pageSize.getWidth(),z=u.internal.pageSize.getHeight(),y=24,k=H-y*2,S=(z-y*2)*n.scrollWidth/k,l=g.height/n.scrollHeight,te=Array.from(t.querySelectorAll(".doc-section, .doc-item, .doc-skill-row, .doc-card, .doc-footer")).map(p=>Math.round(p.offsetTop)).filter((p,f,j)=>p>0&&j.indexOf(p)===f).sort((p,f)=>p-f),F=[];let _=0;const T=n.scrollHeight;for(;_<T;){const p=Math.min(T,_+S),f=te.filter(j=>j>_+S*.4&&j<=p).pop()??p;if(f<=_){F.push(T);break}F.push(f),_=f}let W=0,Y=0;for(const p of F){const f=Math.floor(W*l),j=Math.min(g.height,Math.ceil(p*l)),R=Math.max(1,j-f),$=document.createElement("canvas");$.width=g.width,$.height=R;const M=$.getContext("2d");if(!M)throw new Error("No se pudo preparar una página del PDF.");M.fillStyle="#edf3ff",M.fillRect(0,0,$.width,$.height),M.drawImage(g,0,f,g.width,R,0,0,g.width,R);const oe=$.toDataURL("image/png"),re=R*k/g.width;Y>0&&u.addPage(),u.addImage(oe,"PNG",y,y,k,re,void 0,"FAST"),W=p,Y+=1}u.save(`${P}.pdf`)},ee=async()=>{try{E(!0),b==="pdf"?await Z():Q();try{await me({format:b,name:P})}catch{}}catch(t){alert(t instanceof Error?t.message:"No se pudo generar el reporte.")}finally{E(!1)}};return o.jsx("div",{className:"fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm",children:o.jsxs("div",{className:"flex h-[min(92vh,920px)] w-full max-w-6xl flex-col overflow-hidden rounded-[30px] border border-white/30 bg-white shadow-[0_40px_120px_-36px_rgba(15,23,42,0.55)]",onWheel:t=>t.stopPropagation(),children:[o.jsxs("div",{className:"flex items-start justify-between gap-4 border-b border-[var(--umss-border)] px-6 py-5",children:[o.jsxs("div",{children:[o.jsx("p",{className:"text-[11px] font-bold uppercase tracking-[0.22em] text-[var(--umss-brand)]",children:e("dashboard.report.preview")}),o.jsx("h2",{className:"mt-2 text-2xl font-semibold text-slate-900",children:e("dashboard.report.title")}),o.jsx("p",{className:"mt-2 text-sm text-slate-500",children:e("dashboard.report.subtitle")})]}),o.jsx("button",{type:"button",onClick:a,className:"flex h-10 w-10 items-center justify-center rounded-full border border-[var(--umss-border)] text-slate-500 transition hover:border-[var(--umss-brand)] hover:text-[var(--umss-brand)]","aria-label":e("dashboard.report.close"),children:o.jsx(ne,{className:"h-5 w-5"})})]}),o.jsxs("div",{className:"grid min-h-0 flex-1 grid-cols-1 overflow-hidden lg:grid-cols-[minmax(280px,0.34fr)_minmax(0,0.66fr)]",children:[o.jsx("aside",{className:"overflow-y-auto border-b border-[var(--umss-border)] bg-[linear-gradient(180deg,rgba(248,250,255,0.98),rgba(255,255,255,0.98))] px-5 py-5 lg:border-r lg:border-b-0 lg:px-6 lg:py-6",children:o.jsxs("div",{className:"rounded-[28px] border border-[var(--umss-border)] bg-white p-5 shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)]",children:[o.jsxs("div",{className:"flex items-start gap-3",children:[o.jsx("div",{className:"rounded-2xl bg-[var(--umss-lavender)] p-3 text-[var(--umss-brand)]",children:o.jsx(V,{className:"h-5 w-5"})}),o.jsxs("div",{children:[o.jsx("h3",{className:"text-sm font-semibold text-slate-900",children:e("dashboard.report.selectFormat")}),o.jsx("p",{className:"mt-1 text-xs leading-relaxed text-slate-500",children:e("dashboard.report.includePhoto")})]})]}),o.jsx("div",{className:"mt-5 space-y-3",children:[{value:"pdf",icon:o.jsx(le,{className:"h-4 w-4"}),subtitle:"A4 / print-ready"},{value:"word",icon:o.jsx(V,{className:"h-4 w-4"}),subtitle:"Editable .doc compatible"}].map(t=>o.jsx("button",{type:"button",onClick:()=>h(t.value),className:`rounded-3xl border p-4 text-left transition ${b===t.value?"border-[var(--umss-brand)] bg-[var(--umss-lavender)] shadow-[0_12px_24px_-20px_rgba(80,72,229,0.9)]":"border-[var(--umss-border)] bg-white hover:border-[rgba(80,72,229,0.3)]"} w-full`,children:o.jsxs("div",{className:"flex items-center gap-3",children:[o.jsx("div",{className:"rounded-2xl bg-white/80 p-2 text-[var(--umss-brand)] shadow-sm",children:t.icon}),o.jsxs("div",{children:[o.jsx("p",{className:"text-lg font-semibold text-[var(--umss-brand)]",children:t.value==="pdf"?e("dashboard.report.pdf"):e("dashboard.report.word")}),o.jsx("p",{className:"text-xs text-slate-500",children:t.subtitle})]})]})},t.value))}),o.jsx("button",{type:"button",onClick:()=>x(t=>!t),className:"mt-4 w-full rounded-2xl border border-[var(--umss-border)] bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-[var(--umss-brand)] hover:text-[var(--umss-brand)]",children:"Configuración de contenido"}),i?o.jsx("div",{className:"mt-3 space-y-2 rounded-[24px] border border-[var(--umss-border)] bg-[var(--umss-surface)] p-3",children:[["contact","Contacto"],["trajectory","Trayectoria resumida"],["technicalSkills","Habilidades técnicas"],["softSkills","Habilidades blandas"],["projects","Proyectos"],["experience","Experiencia y formación"]].map(([t,n])=>o.jsxs("label",{className:"flex items-center justify-between gap-3 rounded-2xl bg-white px-3 py-2 text-sm text-slate-700",children:[o.jsx("span",{children:n}),o.jsx("input",{type:"checkbox",checked:v[t],onChange:w=>X(N=>({...N,[t]:w.target.checked}))})]},t))}):null,o.jsxs("button",{type:"button",onClick:ee,disabled:C,className:"mt-5 flex h-12 w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[var(--umss-brand)] via-[#5b63ff] to-[var(--umss-accent)] text-sm font-semibold text-white shadow-[0_18px_32px_-22px_rgba(80,72,229,0.9)] transition hover:opacity-95 disabled:cursor-not-allowed disabled:opacity-60",children:[o.jsx(pe,{className:"h-4 w-4"}),e(C?"dashboard.report.generating":"dashboard.report.download")]})]})}),o.jsxs("div",{className:"min-h-0 overflow-y-auto overscroll-contain bg-[linear-gradient(180deg,#f4f7ff_0%,#eef3ff_100%)] px-4 py-5 sm:px-6 lg:px-7",children:[o.jsx("div",{className:"overflow-hidden rounded-[28px] border border-[var(--umss-border)] bg-white shadow-[0_24px_60px_-40px_rgba(15,23,42,0.35)]",children:o.jsx("iframe",{ref:L,title:e("dashboard.report.preview"),srcDoc:O,onLoad:()=>I(!0),className:"h-[66vh] min-h-[520px] w-full bg-[#edf3ff] lg:h-[calc(92vh-200px)]"})}),J?null:o.jsx("p",{className:"mt-3 text-xs text-slate-500",children:e("common.loading")})]})]})]})})}export{we as PortfolioReportModal};
