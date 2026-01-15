const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { createElement } = window.wp.element;
const { decodeEntities } = window.wp.htmlEntities;
const { getSetting } = window.wc.wcSettings;

const settings = getSetting("cregis_data", {});

const defaultLabel = decodeEntities(settings.title) || "Cryptocurrency";
const defaultDescription = decodeEntities(settings.description) || "Pay securely with cryptocurrency via Cregis.";

const Label = (props) => {
  const { PaymentMethodLabel } = props.components;
  return createElement(PaymentMethodLabel, { text: defaultLabel });
};

const Content = () => {
  return createElement("div", { className: "wc-block-components-payment-method-content" }, createElement("p", {}, defaultDescription));
};

const CregisPaymentMethod = {
  name: "cregis",
  label: createElement(Label, null),
  content: createElement(Content, null),
  edit: createElement(Content, null),
  canMakePayment: () => true,
  ariaLabel: defaultLabel,
  supports: {
    features: settings.supports || [],
  },
};

registerPaymentMethod(CregisPaymentMethod);
