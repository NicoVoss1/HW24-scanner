<style>
.hw24-grid{width:100%;min-width:0;max-width:100%;color:#18314a;--grid-bg:#fff;--grid-alt:#f4f8fc;--grid-border:#bacdde;--grid-head:#dceeff;--grid-text:#18314a;--grid-input:#fff}
html.dark .hw24-grid{color:#e8f1fb;--grid-bg:#182434;--grid-alt:#202f43;--grid-border:#496078;--grid-head:#244969;--grid-text:#e8f1fb;--grid-input:#101c2c}
.hw24-grid *{box-sizing:border-box}
.hw24-grid .hw24-toolbar{display:flex;flex-wrap:wrap;gap:12px;align-items:end;padding:14px;background:var(--grid-alt);border:1px solid var(--grid-border);border-radius:8px;margin:12px 0}
.hw24-grid label{display:flex;flex-direction:column;gap:5px;font-size:12px;font-weight:700;color:var(--grid-text);min-width:0;flex:1 1 190px}
.hw24-grid input[type=text],.hw24-grid input[type=search],.hw24-grid select{display:block;width:100%;min-width:0;max-width:100%;height:42px;border:1px solid #829bb2;border-radius:7px;background:var(--grid-input);color:var(--grid-text);padding:8px 12px;font-size:14px;box-shadow:0 1px 2px #0001}
.hw24-grid input::placeholder{color:#64748b;opacity:1}
html.dark .hw24-grid input::placeholder{color:#aabed2}
.hw24-grid input:focus,.hw24-grid select:focus,.hw24-grid button:focus-visible{outline:2px solid #3b82f6;outline-offset:2px}
.hw24-grid .hw24-btn{border:1px solid #829bb2;background:var(--grid-bg);color:var(--grid-text);font-weight:700;font-size:13px;border-radius:7px;padding:8px 14px;min-height:40px;cursor:pointer}
.hw24-grid .hw24-btn:hover{background:var(--grid-head)}
.hw24-grid .hw24-btn:disabled{opacity:.5;cursor:not-allowed}
.hw24-grid .hw24-check{display:flex;flex-direction:row;align-items:center;gap:8px;flex:0 1 auto;min-height:42px}
.hw24-grid .hw24-check input{width:17px;height:17px;accent-color:#2563eb}
.hw24-grid .hw24-scroll{width:100%;min-width:0;max-width:100%;overflow-x:auto;border:1px solid var(--grid-border);scrollbar-color:#819db8 var(--grid-alt)}
.hw24-grid table{border-collapse:separate;border-spacing:0;text-align:left;background:var(--grid-bg);color:var(--grid-text);font-size:12px}
.hw24-grid .hw24-sheet{width:max-content;min-width:100%;table-layout:fixed}
.hw24-grid th,.hw24-grid td{border-bottom:1px solid var(--grid-border);border-right:1px solid var(--grid-border);padding:8px;vertical-align:top;overflow-wrap:anywhere}
.hw24-grid th{background:var(--grid-head);color:var(--grid-text);font-weight:750}
.hw24-grid .hw24-sheet th,.hw24-grid .hw24-sheet td{width:180px;min-width:180px;max-width:180px}
.hw24-grid .hw24-sheet th:first-child,.hw24-grid .hw24-sheet td:first-child{position:sticky;left:0;z-index:1;width:70px;min-width:70px;max-width:70px;background:var(--grid-head);color:var(--grid-text)}
.hw24-grid .hw24-sheet tr:nth-child(even):not(.hw24-status-lp):not(.hw24-status-we){background:var(--grid-alt)}
.hw24-grid .hw24-status-lp{background:#00b050;color:#082416}
.hw24-grid .hw24-status-we{background:#ffd84d;color:#493800}
.hw24-grid .hw24-sheet th button{width:100%;text-align:left;color:inherit;cursor:pointer;line-height:1.4}
.hw24-grid .hw24-pagebar{display:flex;flex-wrap:wrap;align-items:center;gap:12px;padding:10px 0;color:var(--grid-text)}
.hw24-grid .hw24-colcode{display:block;font-size:11px;opacity:.85}
.hw24-grid details summary{cursor:pointer;text-decoration:underline}
.hw24-grid .hw24-selected td{background:var(--grid-head);color:var(--grid-text)}
.hw24-grid .hw24-edit-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,290px),1fr));gap:16px;margin:16px 0}
.hw24-grid textarea{width:100%;min-height:64px;border:1px solid #829bb2;border-radius:7px;background:var(--grid-input);color:var(--grid-text);padding:10px;font:inherit;resize:vertical}
.hw24-grid .hw24-sheet th:first-child,.hw24-grid .hw24-sheet td:first-child{width:110px;min-width:110px;max-width:110px}
</style>