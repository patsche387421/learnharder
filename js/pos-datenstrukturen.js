/* ============================================================================
 * POS · Datenstrukturen · Test-Tool   (eigenständig, datengetrieben)
 * ----------------------------------------------------------------------------
 * Einbinden:
 *   <link rel="stylesheet" href="pos-datenstrukturen.css">
 *   <div id="ds-tool-root"></div>
 *   <script src="pos-datenstrukturen.js"></script>
 *   <script>
 *     DSTool.mount({
 *       root:    "#ds-tool-root",
 *       dataUrl: "pos-datenstrukturen_data.json",
 *       quizUrl: "pos-datenstrukturen_quiz.json"
 *     });
 *   </script>
 *
 * ============================================================================
 * SO ERWEITERT CLAUDE CODE DAS TOOL  (Vertrag zwischen JSON und JS)
 * ----------------------------------------------------------------------------
 * 1) NEUE STRUKTUR, BESTEHENDER visualType  -> NUR JSON anfassen.
 *    In pos-datenstrukturen_data.json unter "structures" eintragen:
 *      "Name": { label, kind, namespace, description,
 *                visualType: "array|list|stack|queue|dictionary|set",
 *                seed: {...}, methods: { ... } }
 *    seed je visualType:
 *      array       -> { value: "Text" }
 *      list/stack/queue/set -> { items: [...] }
 *      dictionary  -> { entries: [ {key, value}, ... ] }
 *    methods: jede Methode = { label, params[], signature, returnType, description }
 *    param   = { name, label, type: "string|int|intset", placeholder }
 *    -> Struktur erscheint automatisch in der Auswahl, Methoden-Dropdown,
 *       Parameter-Formular und Visualisierung. Keine JS-Änderung nötig.
 *
 * 2) NEUE METHODE bei bestehender Struktur:
 *    a) JSON: Methode unter "methods" ergänzen.
 *    b) JS: passenden Eintrag in EXEC[visualType] ergänzen (siehe unten).
 *       Fehlt der Eintrag, loggt das Tool sauber "Methode nicht implementiert".
 *
 * 3) GANZ NEUER visualType (z.B. "tree"):
 *    a) Renderer in VIZ["tree"] ergänzen  (baut HTML aus dem State).
 *    b) Executor in EXEC["tree"] ergänzen (mutiert State, ruft S.log()).
 *    c) State-Init in seedState() ergänzen.
 *
 * 4) QUIZ erweitern: pos-datenstrukturen_quiz.json (KI_VORLAGE-Schema).
 * ==========================================================================*/

(function () {
  "use strict";

  /* ---------- kleine Helfer ---------- */
  function esc(s) {
    if (s === null || s === undefined) return "";
    return String(s)
      .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;").replace(/'/g, "&#39;");
  }
  function el(html) {
    const t = document.createElement("template");
    t.innerHTML = html.trim();
    return t.content.firstElementChild;
  }
  function $(root, sel) { return root.querySelector(sel); }

  /* ============================================================
   *  EXECUTORS  –  je visualType eine Methodentabelle
   *  Signatur:  fn(p, S)   p = Parameter,  S = { st, log, sd }
   * ============================================================ */
  const EXEC = {
    array: {
      Length:    (p, S) => S.log(`Rückgabe: ${S.st.text.length}`, "result"),
      IndexOf:   (p, S) => { const i = S.st.text.indexOf(p.value);
                   S.log(`Rückgabe: ${i}`, "result");
                   S.log(i >= 0 ? `"${p.value}" gefunden an Index ${i}` : `"${p.value}" nicht gefunden`, i >= 0 ? "success" : "warning"); },
      Substring2:(p, S) => { const s = S.st.text;
                   if (p.startIndex < 0 || p.startIndex + p.length > s.length || p.length < 0)
                     throw new Error(`ArgumentOutOfRangeException: start=${p.startIndex}, length=${p.length} für String der Länge ${s.length}`);
                   S.st.text = s.substr(p.startIndex, p.length);
                   S.log(`Rückgabe: "${S.st.text}"`, "result"); },
      Replace:   (p, S) => { S.st.text = S.st.text.split(p.oldValue).join(p.newValue);
                   S.log(`Rückgabe: "${S.st.text}"`, "result");
                   S.log(`"${p.oldValue}" → "${p.newValue}"`, "success"); },
      ToUpper:   (p, S) => { S.st.text = S.st.text.toUpperCase(); S.log(`Rückgabe: "${S.st.text}"`, "result"); },
      Contains:  (p, S) => { const has = S.st.text.includes(p.value);
                   S.log(`Rückgabe: ${has}`, "result");
                   S.log(has ? `"${p.value}" ist enthalten` : `"${p.value}" ist NICHT enthalten`, has ? "success" : "warning"); },
      Trim:      (p, S) => { const before = S.st.text.length; S.st.text = S.st.text.trim();
                   S.log(`Rückgabe: "${S.st.text}"`, "result");
                   S.log(`${before - S.st.text.length} Leerzeichen entfernt`, "success"); }
    },

    list: {
      Add:      (p, S) => { S.st.items.push(p.item); S.st.flash = S.st.items.length - 1;
                  S.log(`"${p.item}" am Ende hinzugefügt`, "success"); S.log(`Count = ${S.st.items.length}`, "info"); },
      Insert:   (p, S) => { if (p.index < 0 || p.index > S.st.items.length)
                    throw new Error(`ArgumentOutOfRangeException: Index ${p.index} (gültig 0–${S.st.items.length})`);
                  S.st.items.splice(p.index, 0, p.item); S.st.flash = p.index;
                  S.log(`"${p.item}" an Index ${p.index} eingefügt`, "success"); },
      Remove:   (p, S) => { const i = S.st.items.indexOf(p.item);
                  if (i === -1) { S.log("Rückgabe: false", "result"); S.log(`"${p.item}" nicht gefunden`, "warning"); }
                  else { S.st.items.splice(i, 1); S.log("Rückgabe: true", "result"); S.log(`"${p.item}" (Index ${i}) entfernt`, "success"); } },
      RemoveAt: (p, S) => { if (p.index < 0 || p.index >= S.st.items.length)
                    throw new Error(`ArgumentOutOfRangeException: Index ${p.index} (gültig 0–${S.st.items.length - 1})`);
                  const r = S.st.items.splice(p.index, 1)[0]; S.log(`"${r}" an Index ${p.index} entfernt`, "success"); },
      Contains: (p, S) => { const has = S.st.items.includes(p.item);
                  S.log(`Rückgabe: ${has}`, "result");
                  S.log(has ? `"${p.item}" ist in der Liste` : `"${p.item}" NICHT in der Liste`, has ? "success" : "warning"); },
      IndexOf:  (p, S) => { const i = S.st.items.indexOf(p.item);
                  S.log(`Rückgabe: ${i}`, "result");
                  S.log(i >= 0 ? `"${p.item}" an Index ${i}` : `"${p.item}" nicht gefunden`, i >= 0 ? "success" : "warning"); },
      Sort:     (p, S) => { S.st.items.sort((a, b) => String(a).localeCompare(String(b), "de"));
                  S.log("Liste sortiert", "success"); S.log(`[${S.st.items.join(", ")}]`, "info"); },
      Clear:    (p, S) => { S.st.items = []; S.log("Alle Elemente entfernt", "success"); },
      Count:    (p, S) => S.log(`Rückgabe: ${S.st.items.length}`, "result")
    },

    stack: {
      Push:     (p, S) => { S.st.items.unshift(p.item); S.st.flash = 0;
                  S.log(`"${p.item}" auf den Stack (Push)`, "success"); },
      Pop:      (p, S) => { if (!S.st.items.length) throw new Error("InvalidOperationException: Stack ist leer – Pop() nicht möglich");
                  const t = S.st.items.shift(); S.log(`Rückgabe: "${t}"`, "result"); S.log(`"${t}" vom Stack entfernt (Pop)`, "success"); },
      Peek:     (p, S) => { if (!S.st.items.length) throw new Error("InvalidOperationException: Stack ist leer – Peek() nicht möglich");
                  S.st.flash = 0; S.log(`Rückgabe: "${S.st.items[0]}"`, "result"); S.log("Oberstes Element angesehen (ohne Entfernen)", "info"); },
      Contains: (p, S) => { const has = S.st.items.includes(p.item);
                  S.log(`Rückgabe: ${has}`, "result");
                  S.log(has ? `"${p.item}" ist im Stack` : `"${p.item}" NICHT im Stack`, has ? "success" : "warning"); },
      Clear:    (p, S) => { S.st.items = []; S.log("Stack geleert", "success"); },
      Count:    (p, S) => S.log(`Rückgabe: ${S.st.items.length}`, "result")
    },

    queue: {
      Enqueue:  (p, S) => { S.st.items.push(p.item); S.st.flash = S.st.items.length - 1;
                  S.log(`"${p.item}" hinten angestellt (Enqueue)`, "success"); },
      Dequeue:  (p, S) => { if (!S.st.items.length) throw new Error("InvalidOperationException: Queue ist leer – Dequeue() nicht möglich");
                  const f = S.st.items.shift(); S.log(`Rückgabe: "${f}"`, "result"); S.log(`"${f}" vorne entfernt (Dequeue)`, "success"); },
      Peek:     (p, S) => { if (!S.st.items.length) throw new Error("InvalidOperationException: Queue ist leer – Peek() nicht möglich");
                  S.st.flash = 0; S.log(`Rückgabe: "${S.st.items[0]}"`, "result"); S.log("Vorderstes Element angesehen (ohne Entfernen)", "info"); },
      Contains: (p, S) => { const has = S.st.items.includes(p.item);
                  S.log(`Rückgabe: ${has}`, "result");
                  S.log(has ? `"${p.item}" ist in der Queue` : `"${p.item}" NICHT in der Queue`, has ? "success" : "warning"); },
      Clear:    (p, S) => { S.st.items = []; S.log("Queue geleert", "success"); },
      Count:    (p, S) => S.log(`Rückgabe: ${S.st.items.length}`, "result")
    },

    dictionary: {
      Add:         (p, S) => { if (S.st.entries.some(e => e.key === p.key)) throw new Error(`ArgumentException: Key ${p.key} existiert bereits`);
                     S.st.entries.push({ key: p.key, value: p.value }); sortDict(S);
                     S.log(`{${p.key}: "${p.value}"} hinzugefügt`, "success"); S.log(`Count = ${S.st.entries.length}`, "info"); },
      Remove:      (p, S) => { const i = S.st.entries.findIndex(e => e.key === p.key);
                     if (i === -1) { S.log("Rückgabe: false", "result"); S.log(`Key ${p.key} nicht gefunden`, "warning"); }
                     else { const r = S.st.entries.splice(i, 1)[0]; S.log("Rückgabe: true", "result"); S.log(`Key ${p.key} ("${r.value}") entfernt`, "success"); } },
      ContainsKey: (p, S) => { const has = S.st.entries.some(e => e.key === p.key);
                     S.log(`Rückgabe: ${has}`, "result");
                     S.log(has ? `Key ${p.key} vorhanden` : `Key ${p.key} NICHT vorhanden`, has ? "success" : "warning"); },
      TryGetValue: (p, S) => { const e = S.st.entries.find(x => x.key === p.key);
                     if (e) { S.log("Rückgabe: true", "result"); S.log(`out value = "${e.value}"`, "result"); }
                     else { S.log("Rückgabe: false", "result"); S.log(`out value = null (Key ${p.key} fehlt)`, "warning"); } },
      IndexOfKey:  (p, S) => { const i = S.st.entries.findIndex(e => e.key === p.key);
                     S.log(`Rückgabe: ${i}`, "result");
                     S.log(i >= 0 ? `Key ${p.key} an Index ${i}` : "-1 (nicht gefunden)", i >= 0 ? "success" : "warning"); },
      Clear:       (p, S) => { S.st.entries = []; S.log("Alle Einträge entfernt", "success"); },
      Count:       (p, S) => S.log(`Rückgabe: ${S.st.entries.length}`, "result")
    },

    set: {
      Add:           (p, S) => { if (S.st.items.includes(p.item)) { S.log("Rückgabe: false", "result"); S.log(`${p.item} bereits vorhanden – Duplikat ignoriert`, "warning"); }
                       else { S.st.items.push(p.item); S.st.flash = S.st.items.length - 1; S.log("Rückgabe: true", "result"); S.log(`${p.item} hinzugefügt`, "success"); } },
      Remove:        (p, S) => { const i = S.st.items.indexOf(p.item);
                       if (i === -1) { S.log("Rückgabe: false", "result"); S.log(`${p.item} nicht in der Menge`, "warning"); }
                       else { S.st.items.splice(i, 1); S.log("Rückgabe: true", "result"); S.log(`${p.item} entfernt`, "success"); } },
      Contains:      (p, S) => { const has = S.st.items.includes(p.item);
                       S.log(`Rückgabe: ${has}`, "result");
                       S.log(has ? `${p.item} ist in der Menge` : `${p.item} NICHT in der Menge`, has ? "success" : "warning"); },
      IntersectWith: (p, S) => { const o = p.other; S.st.items = S.st.items.filter(x => o.includes(x));
                       S.log(`Schnittmenge mit {${o.join(", ")}}`, "success"); S.log(`Ergebnis: {${S.st.items.join(", ")}}`, "info"); },
      UnionWith:     (p, S) => { p.other.forEach(x => { if (!S.st.items.includes(x)) S.st.items.push(x); });
                       S.log(`Vereinigung mit {${p.other.join(", ")}}`, "success"); S.log(`Ergebnis: {${S.st.items.join(", ")}}`, "info"); },
      ExceptWith:    (p, S) => { const o = p.other; S.st.items = S.st.items.filter(x => !o.includes(x));
                       S.log(`Differenz minus {${o.join(", ")}}`, "success"); S.log(`Ergebnis: {${S.st.items.join(", ")}}`, "info"); },
      Clear:         (p, S) => { S.st.items = []; S.log("Menge geleert", "success"); },
      Count:         (p, S) => S.log(`Rückgabe: ${S.st.items.length}`, "result")
    }
  };

  function sortDict(S) { if (S.sd.sorted) S.st.entries.sort((a, b) => a.key - b.key); }

  /* ============================================================
   *  VISUALISIERER  –  bauen HTML aus dem State
   * ============================================================ */
  const VIZ = {
    array: (st) => {
      const items = st.text.split("");
      if (!items.length) return `<div class="dst-empty">Leere Zeichenkette · string.Empty</div>`;
      return `<div class="dst-mono dst-vislabel">"${esc(st.text)}"</div>
        <div class="dst-cells">${items.map((c, i) =>
          `<div class="dst-cell ${st.flash === i ? "dst-flash" : ""}"><span class="dst-ix">${i}</span>${esc(c)}</div>`).join("")}</div>`;
    },
    list: (st) => {
      if (!st.items.length) return `<div class="dst-empty">Leere Liste</div>`;
      return `<div class="dst-mono dst-vislabel">List&lt;string&gt; · ${st.items.length} Elemente</div>
        <div class="dst-cells">${st.items.map((it, i) =>
          `<div class="dst-cell ${st.flash === i ? "dst-flash" : ""}"><span class="dst-ix">${i}</span>${esc(it)}</div>`).join("")}</div>`;
    },
    stack: (st) => {
      if (!st.items.length) return `<div class="dst-empty">Stack ist leer · Pop/Peek → Exception</div>`;
      return `<div class="dst-stack"><div class="dst-stack-tag">↑ Push / Pop</div>
        ${st.items.map((it, i) =>
          `<div class="dst-stack-row ${i === 0 ? "dst-top" : ""} ${st.flash === i ? "dst-flash" : ""}">
             ${i === 0 ? '<span class="dst-pin">TOP</span>' : ""}${esc(it)}</div>`).join("")}
        <div class="dst-stack-base">Boden</div></div>`;
    },
    queue: (st) => {
      if (!st.items.length) return `<div class="dst-empty">Queue ist leer · Dequeue/Peek → Exception</div>`;
      return `<div class="dst-qdir"><span>← Dequeue (vorne)</span><span>Enqueue (hinten) →</span></div>
        <div class="dst-queue">${st.items.map((it, i) =>
          `<div class="dst-qcell ${i === 0 ? "dst-front" : ""} ${st.flash === i ? "dst-flash" : ""}">
             <span class="dst-ix">${i === 0 ? "FRONT" : "[" + i + "]"}</span>${esc(it)}</div>`).join("")}</div>`;
    },
    dictionary: (st, sd) => {
      if (!st.entries.length) return `<div class="dst-empty">Keine Einträge</div>`;
      const sorted = !!sd.sorted;
      return `<table class="dst-table"><thead><tr><th>Key</th><th>Value</th>${sorted ? "<th>Index</th>" : ""}</tr></thead>
        <tbody>${st.entries.map((e, i) =>
          `<tr><td class="dst-key">${esc(e.key)}</td><td>${esc(e.value)}</td>${sorted ? `<td class="dst-ix">[${i}]</td>` : ""}</tr>`).join("")}</tbody></table>
        ${sorted ? `<div class="dst-mono dst-note">↑ automatisch nach Key sortiert</div>` : ""}`;
    },
    set: (st) => {
      if (!st.items.length) return `<div class="dst-empty">Leere Menge · { }</div>`;
      return `<div class="dst-mono dst-vislabel">{ ${st.items.join(", ")} }</div>
        <div class="dst-set">${st.items.map((it, i) =>
          `<div class="dst-chip ${st.flash === i ? "dst-flash" : ""}">${esc(it)}</div>`).join("")}</div>`;
    }
  };

  function seedState(sd) {
    const seed = sd.seed || {};
    return {
      text: seed.value !== undefined ? seed.value : "",
      items: Array.isArray(seed.items) ? seed.items.slice() : [],
      entries: Array.isArray(seed.entries) ? seed.entries.map(e => ({ key: e.key, value: e.value })) : [],
      flash: -1
    };
  }

  function countLabel(sd, st) {
    if (sd.visualType === "array") return `Länge: ${st.text.length}`;
    if (sd.visualType === "dictionary") return `Count: ${st.entries.length}`;
    return `Count: ${st.items.length}`;
  }

  /* ============================================================
   *  TOOL-INSTANZ
   * ============================================================ */
  function build(rootEl, DATA, QUIZ) {
    const structs = DATA.structures || {};
    const firstKey = Object.keys(structs)[0];

    rootEl.classList.add("dst");
    rootEl.innerHTML = `
      <header class="dst-head">
        <div class="dst-eyebrow">${esc(DATA.fach || "POS")} · ${esc(DATA.thema || "Datenstrukturen")}</div>
        <h2 class="dst-title">Werkbank</h2>
        <nav class="dst-tabs">
          <button class="dst-tab dst-active" data-tab="labor">Labor</button>
          <button class="dst-tab" data-tab="quiz">Quiz</button>
        </nav>
      </header>

      <section class="dst-panel" data-view="labor">
        <div class="dst-grid">
          <aside class="dst-rail">
            <label class="dst-lbl">Struktur</label>
            <select class="dst-select" id="dst-struct"></select>
            <p class="dst-mono dst-desc" id="dst-desc"></p>

            <label class="dst-lbl">Methode</label>
            <select class="dst-select" id="dst-method"></select>
            <div id="dst-methodinfo"></div>
            <div id="dst-params"></div>

            <div class="dst-actions">
              <button class="dst-btn dst-primary" id="dst-run">Ausführen</button>
              <button class="dst-btn" id="dst-reset">Reset</button>
            </div>
          </aside>

          <main class="dst-work">
            <div class="dst-workbar">
              <span class="dst-mono" id="dst-vislabel"></span>
              <span class="dst-badge" id="dst-count"></span>
            </div>
            <div class="dst-viz" id="dst-viz"></div>
            <div class="dst-logbar"><span>Konsole</span><button class="dst-link" id="dst-clear">leeren</button></div>
            <div class="dst-log" id="dst-log"></div>
          </main>
        </div>
      </section>

      <section class="dst-panel dst-hidden" data-view="quiz">
        <div class="dst-quiz" id="dst-quiz"></div>
      </section>`;

    const ui = {
      struct: $(rootEl, "#dst-struct"), method: $(rootEl, "#dst-method"),
      desc: $(rootEl, "#dst-desc"), methodinfo: $(rootEl, "#dst-methodinfo"),
      params: $(rootEl, "#dst-params"), viz: $(rootEl, "#dst-viz"),
      vislabel: $(rootEl, "#dst-vislabel"), count: $(rootEl, "#dst-count"),
      log: $(rootEl, "#dst-log"), quiz: $(rootEl, "#dst-quiz")
    };

    let curKey = firstKey, sd = structs[firstKey], st = seedState(sd);

    /* tabs */
    rootEl.querySelectorAll(".dst-tab").forEach(b => b.addEventListener("click", () => {
      rootEl.querySelectorAll(".dst-tab").forEach(x => x.classList.remove("dst-active"));
      b.classList.add("dst-active");
      rootEl.querySelectorAll(".dst-panel").forEach(p =>
        p.classList.toggle("dst-hidden", p.dataset.view !== b.dataset.tab));
    }));

    /* struktur-auswahl */
    ui.struct.innerHTML = Object.entries(structs)
      .map(([k, v]) => `<option value="${esc(k)}">${esc(v.label || k)}</option>`).join("");
    ui.struct.addEventListener("change", () => selectStruct(ui.struct.value));
    ui.method.addEventListener("change", selectMethod);
    $(rootEl, "#dst-run").addEventListener("click", run);
    $(rootEl, "#dst-reset").addEventListener("click", () => { st = seedState(sd); st.flash = -1; clearLog(); log("Zustand zurückgesetzt", "warning"); render(); });
    $(rootEl, "#dst-clear").addEventListener("click", clearLog);

    function selectStruct(key) {
      curKey = key; sd = structs[key]; st = seedState(sd);
      ui.desc.textContent = sd.description || "";
      ui.method.innerHTML = `<option value="">– Methode wählen –</option>` +
        Object.entries(sd.methods).map(([k, m]) => `<option value="${esc(k)}">${esc(m.label)}</option>`).join("");
      ui.methodinfo.innerHTML = ""; ui.params.innerHTML = "";
      clearLog(); render();
    }

    function selectMethod() {
      const k = ui.method.value;
      if (!k) { ui.methodinfo.innerHTML = ""; ui.params.innerHTML = ""; return; }
      const m = sd.methods[k];
      ui.methodinfo.innerHTML = `
        <div class="dst-minfo">
          <code class="dst-sig">${esc(m.signature)}</code>
          <p>${esc(m.description)}</p>
          <span class="dst-ret">→ ${esc(m.returnType)}</span>
        </div>`;
      ui.params.innerHTML = m.params.length
        ? m.params.map(p => `
            <div class="dst-field">
              <label for="dst-p-${esc(p.name)}">${esc(p.label)}</label>
              <input id="dst-p-${esc(p.name)}" type="${p.type === "int" ? "number" : "text"}"
                     placeholder="${esc(p.placeholder)}" value="${esc(p.placeholder)}">
            </div>`).join("")
        : `<div class="dst-mono dst-note">Keine Parameter</div>`;
    }

    function run() {
      const k = ui.method.value;
      if (!k) { log("Bitte zuerst eine Methode wählen.", "warning"); return; }
      const m = sd.methods[k], p = {};
      for (const par of m.params) {
        const v = $(rootEl, "#dst-p-" + par.name).value;
        if (par.type === "int") p[par.name] = parseInt(v, 10);
        else if (par.type === "intset") p[par.name] = v.split(",").map(s => parseInt(s.trim(), 10)).filter(n => !isNaN(n));
        else p[par.name] = v;
      }
      const table = EXEC[sd.visualType] || {};
      const fn = table[k];
      st.flash = -1;
      try {
        if (fn) fn(p, { st, sd, log });
        else log(`Methode "${k}" für visualType "${sd.visualType}" nicht implementiert.`, "warning");
        render();
      } catch (err) { log("🔴 " + err.message, "error"); }
    }

    function render() {
      const renderer = VIZ[sd.visualType];
      ui.viz.innerHTML = renderer ? renderer(st, sd) : `<div class="dst-empty">Kein Renderer für "${esc(sd.visualType)}"</div>`;
      ui.vislabel.textContent = sd.label;
      ui.count.textContent = countLabel(sd, st);
    }

    function log(msg, type = "info") {
      const ph = ui.log.querySelector(".dst-log-empty"); if (ph) ph.remove();
      const labels = { info: "Info", success: "OK", error: "Exception", warning: "Warnung", result: "Rückgabe" };
      const row = el(`<div class="dst-logrow dst-${type}"><span class="dst-logtag">${labels[type] || type}</span>${esc(msg)}</div>`);
      ui.log.appendChild(row); ui.log.scrollTop = ui.log.scrollHeight;
    }
    function clearLog() { ui.log.innerHTML = `<div class="dst-log-empty">Noch keine Ausgabe · Methode ausführen →</div>`; }

    /* ---------- QUIZ ---------- */
    function buildQuiz() {
      const qs = (QUIZ && QUIZ.fragen) || [];
      if (!qs.length) { ui.quiz.innerHTML = `<div class="dst-empty">Kein Quiz geladen.</div>`; return; }
      ui.quiz.innerHTML = `
        <div class="dst-quizhead"><span class="dst-mono">${esc(QUIZ.fach || "Quiz")}</span>
          <span class="dst-badge" id="dst-score">0 / ${qs.length}</span></div>
        ${qs.map((q, qi) => `
          <article class="dst-card" data-q="${qi}" data-sol="${q.loesung}">
            <div class="dst-qnum">Frage ${qi + 1}</div>
            <p class="dst-qtext">${esc(q.frage)}</p>
            <div class="dst-opts">${q.optionen.map((o, oi) =>
              `<button class="dst-opt" data-o="${oi}">${esc(o)}</button>`).join("")}</div>
          </article>`).join("")}`;
      let score = 0; const done = {};
      ui.quiz.querySelectorAll(".dst-card").forEach(card => {
        const sol = +card.dataset.sol, qi = card.dataset.q;
        card.querySelectorAll(".dst-opt").forEach(btn => btn.addEventListener("click", () => {
          if (done[qi]) return; done[qi] = true;
          const pick = +btn.dataset.o;
          card.querySelectorAll(".dst-opt").forEach((b, i) => {
            b.disabled = true;
            if (i === sol) b.classList.add("dst-correct");
            else if (i === pick) b.classList.add("dst-wrong");
          });
          if (pick === sol) score++;
          $(rootEl, "#dst-score").textContent = `${score} / ${qs.length}`;
        }));
      });
    }

    /* init */
    selectStruct(curKey);
    buildQuiz();
  }

  /* ============================================================
   *  PUBLIC API
   * ============================================================ */
  const DSTool = {
    async mount(opts) {
      const rootEl = typeof opts.root === "string" ? document.querySelector(opts.root) : opts.root;
      if (!rootEl) { console.error("DSTool: root nicht gefunden"); return; }
      try {
        const [d, q] = await Promise.all([
          fetch(opts.dataUrl).then(r => { if (!r.ok) throw new Error(opts.dataUrl + " nicht erreichbar"); return r.json(); }),
          fetch(opts.quizUrl).then(r => r.ok ? r.json() : { fragen: [] }).catch(() => ({ fragen: [] }))
        ]);
        build(rootEl, d, q);
      } catch (e) {
        rootEl.innerHTML = `<div class="dst dst-empty">Daten konnten nicht geladen werden:<br>${esc(e.message)}<br><br>
          Tipp: lokal per Server starten (z.B. <code>npx serve</code>), nicht per file://</div>`;
        console.error(e);
      }
    }
  };

  window.DSTool = DSTool;
})();
