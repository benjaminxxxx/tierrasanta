// resources/js/handsontable.js
// Handsontable desde npm (antes: CDN "latest" + copia local antigua en public/handsontable).
// La versión la fija package.json. Las vistas usan `new Handsontable(...)` dentro de Alpine,
// por eso se expone en window antes de que Livewire inicie Alpine.

import Handsontable from "handsontable";
import { registerTheme, mainTheme } from "handsontable/themes";
import { registerLanguageDictionary, esMX } from "handsontable/i18n";
import "handsontable/styles/ht-theme-main.min.css";
import { registrarHora24 } from "./hora24";
import { registrarFechasHandsontable, registrarPegadoFechas } from "./fechas";

registerTheme(mainTheme);
registerLanguageDictionary(esMX); // window.HstConfig usa language: "es-MX"
registrarHora24(Handsontable); // tipo de celda "hora24" (HH:mm)
registrarFechasHandsontable(Handsontable); // fechas pegadas (31/01/2023, serie de Excel…) → formato de la columna
registrarPegadoFechas(); // y en los <input type="date">

window.Handsontable = Handsontable;

export default Handsontable;
