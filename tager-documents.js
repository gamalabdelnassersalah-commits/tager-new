(function () {
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

  const urlParams = new URLSearchParams(window.location.search);

  function parseDataFromUrl() {
    const raw = urlParams.get("data");
    if (!raw) return null;
    try {
      return JSON.parse(decodeURIComponent(raw));
    } catch (error) {
      console.warn("Invalid data parameter. Falling back to sample data.", error);
      return null;
    }
  }

  function mergeData(base, custom) {
    const merged = { ...base, ...(custom || {}) };
    merged.totals = { ...base.totals, ...((custom && custom.totals) || {}) };
    merged.items = (custom && Array.isArray(custom.items)) ? custom.items : base.items;
    return merged;
  }

  function formatNumber(value) {
    const numericValue = Number(value || 0);
    return numericValue.toLocaleString("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  function setText(selector, value) {
    document.querySelectorAll(selector).forEach((el) => {
      el.textContent = value == null ? "" : value;
    });
  }

  function buildItems(items, currency) {
    const tbody = document.querySelector("[data-items-body]");
    if (!tbody) return;
    tbody.innerHTML = "";
    items.forEach((item) => {
      const row = document.createElement("tr");
      row.innerHTML = `
        <td class="item-cell"><span>${item.name || ""}</span><span class="item-icon"><svg><use href="assets/img/icon-sprite.svg#box"></use></svg></span></td>
        <td>${item.priceType || ""}</td>
        <td>${item.quantity || 0}</td>
        <td>${currency} ${formatNumber(item.unitPrice)}</td>
        <td>${currency} ${formatNumber(item.total)}</td>
      `;
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

  function render(data) {
    const totals = calculateTotals(data);
    const currency = data.currency || "ج.م";

    setText("[data-field='orderNo']", data.orderNo);
    setText("[data-field='invoiceNo']", data.invoiceNo);
    setText("[data-field='date']", data.date);
    setText("[data-field='platform']", data.platform);
    setText("[data-field='customer']", data.customer);
    setText("[data-field='supplier']", data.supplier);
    setText("[data-field='governorate']", data.governorate);
    setText("[data-field='center']", data.center);
    setText("[data-field='paymentMethod']", data.paymentMethod);
    setText("[data-field='currency']", currency);
    setText("[data-field='status']", data.status);
    setText("[data-field='itemCount']", data.itemCount ?? data.items.length);
    setText("[data-field='signatoryName']", data.signatoryName);
    setText("[data-field='signatoryTitle']", data.signatoryTitle);
    setText("[data-field='signatorySub']", data.signatorySub);
    setText("[data-field='totalWords']", data.totalWords);

    setText("[data-money='products']", formatNumber(totals.products));
    setText("[data-money='discount']", formatNumber(totals.discount));
    setText("[data-money='tax']", formatNumber(totals.tax));
    setText("[data-money='grandTotal']", formatNumber(totals.grandTotal));
    setText("[data-currency]", currency);

    buildItems(data.items, currency);
  }

  window.TagerDocuments = {
    render,
    sampleData: defaultData,
    print: () => window.print(),
    toggleReference: () => document.querySelector(".tager-document")?.classList.toggle("show-reference")
  };

  document.addEventListener("DOMContentLoaded", () => {
    const customData = window.TAGER_DOCUMENT_DATA || parseDataFromUrl();
    render(mergeData(defaultData, customData));

    document.querySelectorAll("[data-print]").forEach((button) => {
      button.addEventListener("click", () => window.print());
    });
    document.querySelectorAll("[data-reference]").forEach((button) => {
      button.addEventListener("click", () => window.TagerDocuments.toggleReference());
    });
  });
})();
