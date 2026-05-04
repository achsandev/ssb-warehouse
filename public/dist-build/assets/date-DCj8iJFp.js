const r=t=>{if(t==null||t==="")return"";const n=t instanceof Date?t:new Date(t);return Number.isNaN(n.getTime())?"":n.toISOString().split("T")[0]};export{r as f};
