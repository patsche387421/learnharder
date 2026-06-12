// Wird beim Netlify-Build ausgeführt.
// Liest SUPABASE_URL und SUPABASE_ANON_KEY aus den Netlify-Umgebungsvariablen
// und schreibt src/js/config.js (das gitignored ist).
const fs   = require('fs');
const path = require('path');

const url = process.env.SUPABASE_URL      || '';
const key = process.env.SUPABASE_ANON_KEY || '';

if (!url || !key) {
  console.error('FEHLER: SUPABASE_URL oder SUPABASE_ANON_KEY nicht gesetzt!');
  process.exit(1);
}

const content =
  "const SUPABASE_URL      = '" + url + "';\n" +
  "const SUPABASE_ANON_KEY = '" + key + "';\n";

const outPath = path.join(__dirname, '../src/js/config.js');
fs.writeFileSync(outPath, content, 'utf8');
console.log('config.js generiert → ' + outPath);
