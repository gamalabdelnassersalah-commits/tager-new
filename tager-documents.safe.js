(function () {
  "use strict";

  const defaultData = {
    orderNo: "TG-1001",
    invoiceNo: "INV-1001",
    date: "2026/7/6",
    platform: "منصة تاجر",
    customer: "Gamal Gemy",
    supplier: "شركة الأخوة",
    governorate: "الدقهلية",
    center: "المنصورة",
    paymentMethod: "نقدي عند الاستلام",
    currency: "ج.م",
    status: "جديد",
    itemCount: 1,
    totalWords: "خمسة آلاف جنيه مصري لا غير",
    items: [
      { name: "بند تجريبي", priceType: "سعر الشراء", quantity: 5, unitPrice: 1000, total: 5000 }
    ],
    totals: {
      products: 5000,
      discount: 0,
      tax: 0,
      grandTotal: 5000
    },
    signatoryName: "Gamal",
    signatoryTitle: "GAMAL",
    signatorySub: "Authorized Signatory"
  };

  function getUrlData() {
    const params = new URLSearchParams(window.location.search);
    const raw = params.get("data");
    if (!raw) return null;
    try {
      return JSON.parse(decodeURIComponent(raw));
    } catch (error) {
      console.warn("Tager document: invalid data parameter. Using sample data.", error);
      return null;
    }
  }

  function mergeData(base, custom) {
    const incoming = custom || {};
    const merged = { ...base, ...incoming };
    merged.totals = { ...base.totals, ...(incoming.totals || {}) };
    merged.items = Array.isArray(incoming.items) ? incoming.items : base.items;
    return merged;
  }

  function formatNumber(value) {
    const numericValue = Number(value || 0);
    return numericValue.toLocaleString("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  function setText(root, selector, value) {
    root.querySelectorAll(selector).forEach((el) => {
      el.textContent = value == null ? "" : String(value);
    });
  }

  function buildItems(root, items, currency) {
    const tbody = root.querySelector("[data-items-body]");
    if (!tbody) return;
    tbody.innerHTML = "";
    items.forEach((item) => {
      const row = document.createElement("tr");
      const name = item.name || "";
      const priceType = item.priceType || "";
      const quantity = item.quantity || 0;
      const unitPrice = `${currency} ${formatNumber(item.unitPrice)}`;
      const total = `${currency} ${formatNumber(item.total)}`;

      row.innerHTML = `
        <td class="item-cell"><span></span><span class="item-icon"><svg><use href="assets/img/icon-sprite.svg#box"></use></svg></span></td>
        <td></td><td></td><td></td><td></td>
      `;
      const cells = row.querySelectorAll("td");
      cells[0].querySelector("span").textContent = name;
      cells[1].textContent = priceType;
      cells[2].textContent = quantity;
      cells[3].textContent = unitPrice;
      cells[4].textContent = total;
      tbody.appendChild(row);
    });
  }

  function calculateTotals(data) {
    const products = data.totals.products ?? data.items.reduce((sum, item) => sum + Number(item.total || 0), 0);
    const discount = Number(data.totals.discount || 0);
    const tax = Number(data.totals.tax || 0);
    const grandTotal = data.totals.grandTotal ?? (products - discount + tax);
    return { products, discount, tax, grandTotal };
  }

  function render(data, rootElement) {
    const root = rootElement || document.querySelector(".tgr-doc-scope");
    if (!root) return;

    const safeData = mergeData(defaultData, data);
    const totals = calculateTotals(safeData);
    const currency = safeData.currency || "ج.م";

    setText(root, "[data-field='orderNo']", safeData.orderNo);
    setText(root, "[data-field='invoiceNo']", safeData.invoiceNo);
    setText(root, "[data-field='date']", safeData.date);
    setText(root, "[data-field='platform']", safeData.platform);
    setText(root, "[data-field='customer']", safeData.customer);
    setText(root, "[data-field='supplier']", safeData.supplier);
    setText(root, "[data-field='governorate']", safeData.governorate);
    setText(root, "[data-field='center']", safeData.center);
    setText(root, "[data-field='paymentMethod']", safeData.paymentMethod);
    setText(root, "[data-field='currency']", currency);
    setText(root, "[data-field='status']", safeData.status);
    setText(root, "[data-field='itemCount']", safeData.itemCount ?? safeData.items.length);
    setText(root, "[data-field='signatoryName']", safeData.signatoryName);
    setText(root, "[data-field='signatoryTitle']", safeData.signatoryTitle);
    setText(root, "[data-field='signatorySub']", safeData.signatorySub);
    setText(root, "[data-field='totalWords']", safeData.totalWords);

    setText(root, "[data-money='products']", formatNumber(totals.products));
    setText(root, "[data-money='discount']", formatNumber(totals.discount));
    setText(root, "[data-money='tax']", formatNumber(totals.tax));
    setText(root, "[data-money='grandTotal']", formatNumber(totals.grandTotal));
    setText(root, "[data-currency]", currency);

    buildItems(root, safeData.items, currency);
  }

  function toggleReference(rootElement) {
    const root = rootElement || document.querySelector(".tgr-doc-scope");
    const documentEl = root && root.querySelector(".tager-document");
    if (documentEl) documentEl.classList.toggle("show-reference");
  }

  window.TagerDocuments = {
    render,
    sampleData: defaultData,
    print: () => window.print(),
    toggleReference
  };

  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".tgr-doc-scope").forEach((root) => {
      const customData = window.TAGER_DOCUMENT_DATA || getUrlData();
      render(customData, root);

      root.querySelectorAll("[data-print]").forEach((button) => {
        button.addEventListener("click", () => window.print());
      });
      root.querySelectorAll("[data-reference]").forEach((button) => {
        button.addEventListener("click", () => toggleReference(root));
      });
    });
  });
})();
