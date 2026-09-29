// resources/js/handsontable.js
// Handsontable desde npm (antes: CDN "latest" + copia local antigua en public/handsontable).
// La versión la fija package.json. Las vistas usan `new Handsontable(...)` dentro de Alpine,
// por eso se expone en window antes de que Livewire inicie Alpine.

import Handsontable from "handsontable";
import { registerTheme, mainTheme } from "handsontable/themes";
import { registerLanguageDictionary, esMX } from "handsontable/i18n";
import "handsontable/styles/ht-theme-main.min.css";

registerTheme(mainTheme);
registerLanguageDictionary(esMX); // window.HstConfig usa language: "es-MX"

window.Handsontable = Handsontable;

export default Handsontable;
