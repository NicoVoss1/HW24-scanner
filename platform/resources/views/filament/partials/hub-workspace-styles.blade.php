<style>
/* Shared admin workspace baseline. Native Filament selects and inputs retain
   their behavior; custom Blade inputs no longer depend on uncompiled utilities. */
.fi-main input:not([type=checkbox]):not([type=radio]):not([type=hidden]):not([type=file]):not([type=range]):not([type=color]),
.fi-main select,.fi-main textarea{
    border:1px solid #8199b0;
    border-radius:7px;
    background-color:#fff;
    color:#172f46;
    min-height:40px;
    padding:8px 11px;
}
html.dark .fi-main input:not([type=checkbox]):not([type=radio]):not([type=hidden]):not([type=file]):not([type=range]):not([type=color]),
html.dark .fi-main select,html.dark .fi-main textarea{
    border-color:#69829d;background-color:#172638;color:#eef5ff;
}
.fi-main input::placeholder,.fi-main textarea::placeholder{color:#64748b;opacity:1}
html.dark .fi-main input::placeholder,html.dark .fi-main textarea::placeholder{color:#b4c6d9}
.fi-main input:focus-visible,.fi-main select:focus-visible,.fi-main textarea:focus-visible{
    outline:2px solid #60a5fa;outline-offset:2px;
}
.fi-main input[type=checkbox],.fi-main input[type=radio]{accent-color:#2563eb}
.fi-main .fi-page{min-width:0}
</style>