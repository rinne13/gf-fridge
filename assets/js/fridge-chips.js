(function () {
  function el(q, ctx) {
    return (ctx || document).querySelector(q);
  }
  function els(q, ctx) {
    return Array.from((ctx || document).querySelectorAll(q));
  }

  function parseCSV(s) {
    return s
      .split(",")
      .map((x) => x.trim())
      .filter(Boolean);
  }
  function toCSV(arr) {
    return arr.join(", ");
  }

  async function fetchVocabulary() {
    try {
      const res = await fetch(`${GF_FRIDGE.rest}/ingredients`);
      if (!res.ok) throw new Error("Bad response");
      const data = await res.json();
      return Array.isArray(data.ingredients) ? data.ingredients : [];
    } catch (e) {
      return [];
    }
  }

  function makeChip(text, onRemove) {
    const chip = document.createElement("span");
    chip.className = "gf-chip";
    chip.textContent = text;
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "gf-chip-x";
    btn.setAttribute(
      "aria-label",
      (GF_FRIDGE.i18n && GF_FRIDGE.i18n.remove) || "Remove"
    );
    btn.textContent = "×";
    btn.addEventListener("click", () => onRemove(text));
    chip.appendChild(btn);
    return chip;
  }

  function filterSuggest(vocab, query, existing) {
    const q = query.toLowerCase().trim();
    if (!q) return [];
    const exSet = new Set(existing.map((s) => s.toLowerCase()));
    return vocab
      .filter((x) => !exSet.has(x.toLowerCase()))
      .filter((x) => x.indexOf(q) !== -1)
      .slice(0, 8);
  }

  function renderSuggest(container, items, onPick) {
    container.innerHTML = "";
    items.forEach((it, idx) => {
      const opt = document.createElement("div");
      opt.className = "gf-suggest-item";
      opt.setAttribute("role", "option");
      opt.setAttribute("tabindex", "0");
      opt.textContent = it;
      opt.addEventListener("click", () => onPick(it));
      opt.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          onPick(it);
        }
      });
      container.appendChild(opt);
    });
    container.style.display = items.length ? "block" : "none";
  }

  function setup(root, vocab) {
    const list = el(".gf-chip-list", root);
    const input = el(".gf-chip-input", root);
    const suggestBox = el(".gf-suggest", root);
    const targetName = root.dataset.target;
    const hidden =
      el(`#${CSS.escape(targetName)}`) || el(`[name="${targetName}"]`);

    // начальная синхронизация из hidden
    let tags = hidden && hidden.value ? parseCSV(hidden.value) : [];

    function syncHidden() {
      if (hidden) hidden.value = toCSV(tags);
    }
    function redraw() {
      list.innerHTML = "";
      tags.forEach((t) => list.appendChild(makeChip(t, removeTag)));
    }
    function addTag(t) {
      const v = t.trim();
      if (!v) return;
      if (tags.some((x) => x.toLowerCase() === v.toLowerCase())) return;
      tags.push(v);
      redraw();
      syncHidden();
      input.value = "";
      renderSuggest(suggestBox, [], () => {});
      input.focus();
    }
    function removeTag(t) {
      tags = tags.filter((x) => x.toLowerCase() !== t.toLowerCase());
      redraw();
      syncHidden();
      input.focus();
    }

    redraw();
    syncHidden();

    input.setAttribute(
      "placeholder",
      (GF_FRIDGE.i18n && GF_FRIDGE.i18n.placeholder) ||
        "Type an ingredient and press Enter…"
    );

    input.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        const val = input.value.trim();
        if (val) addTag(val);
      } else if (e.key === "Backspace" && !input.value && tags.length) {
        tags.pop();
        redraw();
        syncHidden();
      }
    });

    input.addEventListener("input", () => {
      const items = filterSuggest(vocab, input.value, tags);
      renderSuggest(suggestBox, items, (pick) => addTag(pick));
    });

    document.addEventListener("click", (e) => {
      if (!root.contains(e.target)) suggestBox.style.display = "none";
    });
  }

  document.addEventListener("DOMContentLoaded", async () => {
    const vocab = await fetchVocabulary();
    els(".gf-chips").forEach((root) => setup(root, vocab));
  });
})();
