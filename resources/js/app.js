import './bootstrap';
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";

import { Spanish } from "flatpickr/dist/l10n/es.js"; // ✅ importa el idioma

// Establece el idioma globalmente
flatpickr.localize(Spanish);
flatpickr.setDefaults({
  dateFormat: "d/m/Y", // ✅ visible al usuario
  altInput: true,
  altFormat: "d/m/Y", // ✅ formato visible en input
  allowInput: true // permite escribir la fecha manualmente si quieres
});