define(
  [],
  function() {
    'use strict';
    return {
      isDebug: function() {
        return window.checkoutConfig.payment.payfurl.debug;
      },
      getEnv: function() {
        return window.checkoutConfig.payment.payfurl.env;
      },
      getTitle: function() {
        return window.checkoutConfig.payment.payfurl.title;
      },
      getPublicKey: function() {
        return window.checkoutConfig.payment.payfurl.publicKey;
      },
      getToken: function() {
        return window.checkoutConfig.payment.payfurl.payfurlToken;
      },
      getChargeId: function() {
        return window.checkoutConfig.payment.payfurl.payfurlChargeId;
      },
      getPaymentMethodId: function() {
        return window.checkoutConfig.payment.payfurl.paymentMethodId;
      },
      getSaveMyPaymentMethod: function() {
        return window.checkoutConfig.payment.payfurl.payfurlSaveMyPayment;
      },
      getSavedPaymentMethods: function () {
        return window.checkoutConfig.payment.payfurl.getSavedPayments;
      },
      setToken: function(value) {
        window.checkoutConfig.payment.payfurl.payfurlToken = value;
      },
      setChargeId: function(value) {
        window.checkoutConfig.payment.payfurl.payfurlChargeId = value;
      },
      setPaymentMethodId: function(value) {
        window.checkoutConfig.payment.payfurl.paymentMethodId = value;
      },
      setSaveMyPaymentMethod: function(value) {
        window.checkoutConfig.payment.payfurl.payfurlSaveMyPayment = value;
      },
      getProvidersInfo: function() {
        return window.checkoutConfig.payment.payfurl.providersInfo;
      },
      getMethod: function(value) {
        return window.checkoutConfig.payment.payfurl.method;
      },
      setMethod: function(value) {
        window.checkoutConfig.payment.payfurl.method = value;
      },
      getCheckoutInfo: function(providerType) {
        switch (String(providerType).toUpperCase()) {
          case "AZUPAY": return {
            name: "PayID",
            description: "",
            buttonTitle: "Pay with PayID",
            icon: "PayId"
          };
          case "SHIFT": return {
            name: "Shift",
            description: "",
            buttonTitle: "Pay with Shift",
            icon: "Shift"
          };
          case "PAYGLOCAL": return {
            name: "PayGLocal",
            description: "",
            buttonTitle: "Pay with PayGLocal",
            icon: "PayGLocal"
          };
          case "PESAPAL": return {
            name: "PesaPal",
            description: "",
            buttonTitle: "Pay with PesaPal",
            icon: "Pesapal"
          };
          case "PAY_BY_ACCOUNT": return {
            name: "Pay By Account",
            description: "",
            buttonTitle: "Pay with Pay By Account",
            icon: "PayByAccount"
          };
          case "UPI": return {
            name: "UPI",
            description: "",
            buttonTitle: "Pay with UPI",
            icon: "Upi"
          };
          case "ADDI": return {
            name: "Addi",
            description: "",
            buttonTitle: "Pay with Addi",
            icon: "Addi"
          };
          case "AFFIRM": return {
            name: "Affirm",
            description: "",
            buttonTitle: "Pay with Affirm",
            icon: "Affirm"
          };
          case "AFTERPAY": return {
            name: "Afterpay",
            description: "",
            buttonTitle: "Pay with Afterpay",
            icon: "Afterpay"
          };
          case "ALMA": return {
            name: "Alma",
            description: "",
            buttonTitle: "Pay with Alma",
            icon: "Alma"
          };
          case "APLAZAME": return {
            name: "Aplazame",
            description: "",
            buttonTitle: "Pay with Aplazame",
            icon: "Aplazame"
          };
          case "APLAZO": return {
            name: "Aplazo",
            description: "",
            buttonTitle: "Pay with Aplazo",
            icon: "Aplazo"
          };
          case "ATOME": return {
            name: "Atome",
            description: "",
            buttonTitle: "Pay with Atome",
            icon: "Atome"
          };
          case "BILLEASE": return {
            name: "Billease",
            description: "",
            buttonTitle: "Pay with Billease",
            icon: "Billease"
          };
          case "CASHEW": return {
            name: "Cashew",
            description: "",
            buttonTitle: "Pay with Cashew",
            icon: "Cashew"
          };
          case "CASHPRESSO": return {
            name: "Cashpresso",
            description: "",
            buttonTitle: "Pay with Cashpresso",
            icon: "Cashpresso"
          };
          case "CLEARPAY": return {
            name: "Clearpay",
            description: "",
            buttonTitle: "Pay with Clearpay",
            icon: "Clearpay"
          };
          case "CLEO": return {
            name: "Cleo",
            description: "",
            buttonTitle: "Pay with Cleo",
            icon: "Cleo"
          };
          case "DANA": return {
            name: "Dana",
            description: "",
            buttonTitle: "Pay with Dana",
            icon: "Dana"
          };
          case "EASYPAISA": return {
            name: "Easypaisa",
            description: "",
            buttonTitle: "Pay with Easypaisa",
            icon: "Easypaisa"
          };
          case "FINVERO": return {
            name: "Finvero",
            description: "",
            buttonTitle: "Pay with Finvero",
            icon: "Finvero"
          };
          case "FUPAY": return {
            name: "FuPay",
            description: "",
            buttonTitle: "Pay with FuPay",
            icon: "Fupay"
          };
          case "GCASG": return {
            name: "GCasg",
            description: "",
            buttonTitle: "Pay with GCasg",
            icon: "Gcasg"
          };
          case "GRABPAY": return {
            name: "GrabPay",
            description: "",
            buttonTitle: "Pay with GrabPay",
            icon: "Grabpay"
          };
          case "HUMM": return {
            name: "Humm",
            description: "",
            buttonTitle: "Pay with Humm",
            icon: "Humm"
          };
          case "KAKAOPAY": return {
            name: "KakaoPay",
            description: "",
            buttonTitle: "Pay with KakaoPay",
            icon: "Kakaopay"
          };
          case "KLARNA": return {
            name: "Klarna",
            description: "",
            buttonTitle: "Pay with Klarna",
            icon: "Klarna"
          };
          case "KUESKIPAY": return {
            name: "KueskiPay",
            description: "",
            buttonTitle: "Pay with KueskiPay",
            icon: "Kueskipay"
          };
          case "LARA": return {
            name: "Lara",
            description: "",
            buttonTitle: "Pay with Lara",
            icon: "Lara"
          };
          case "LATITUDEPAY": return {
            name: "LatitudePay",
            description: "",
            buttonTitle: "Pay with LatitudePay",
            icon: "Latitudepay"
          };
          case "LAYBUY": return {
            name: "Laybuy",
            description: "",
            buttonTitle: "Pay with Laybuy",
            icon: "Laybuy"
          };
          case "LIMEPAY": return {
            name: "Limepay",
            description: "",
            buttonTitle: "Pay with Limepay",
            icon: "Limepay"
          };
          case "PACE": return {
            name: "Pace",
            description: "",
            buttonTitle: "Pay with Pace",
            icon: "Pace"
          };
          case "PAGALEVE": return {
            name: "Pagaleve",
            description: "",
            buttonTitle: "Pay with Pagaleve",
            icon: "Pagaleve"
          };
          case "PARIBAS": return {
            name: "BNP Paribas",
            description: "",
            buttonTitle: "Pay with BNP Paribas",
            icon: "Paribas"
          };
          case "PAYWITHFOUR": return {
            name: "Pay With Four",
            description: "",
            buttonTitle: "Pay with Pay With Four",
            icon: "Paywithfour"
          };
          case "PAYJUSTNOW": return {
            name: "PayJustNow",
            description: "",
            buttonTitle: "Pay with PayJustNow",
            icon: "Payjustnow"
          };
          case "PAYTM": return {
            name: "PayTM",
            description: "",
            buttonTitle: "Pay with PayTM",
            icon: "Paytm"
          };
          case "PAYBRIGHT": return {
            name: "Paybright",
            description: "",
            buttonTitle: "Pay with Paybright",
            icon: "Paybright"
          };
          case "PAYFLEX": return {
            name: "Payflex",
            description: "",
            buttonTitle: "Pay with Payflex",
            icon: "Payflex"
          };
          case "PAYRIGHT": return {
            name: "Payright",
            description: "",
            buttonTitle: "Pay with Payright",
            icon: "Payright"
          };
          case "POSTPAY": return {
            name: "Postpay",
            description: "",
            buttonTitle: "Pay with Postpay",
            icon: "Postpay"
          };
          case "RATEPAY": return {
            name: "Ratepay",
            description: "",
            buttonTitle: "Pay with Ratepay",
            icon: "Ratepay"
          };
          case "RIVERTY": return {
            name: "Riverty",
            description: "",
            buttonTitle: "Pay with Riverty",
            icon: "Riverty"
          };
          case "SCALAPAY": return {
            name: "Scalapay",
            description: "",
            buttonTitle: "Pay with Scalapay",
            icon: "Scalapay"
          };
          case "SEZZLE": return {
            name: "Sezzle",
            description: "",
            buttonTitle: "Pay with Sezzle",
            icon: "Sezzle"
          };
          case "SHOPBACK": return {
            name: "ShopBack",
            description: "",
            buttonTitle: "Pay with ShopBack",
            icon: "Shopback"
          };
          case "SMARTPAY": return {
            name: "Smartpay",
            description: "",
            buttonTitle: "Pay with Smartpay",
            icon: "Smartpay"
          };
          case "SOFINCO": return {
            name: "Sofinco",
            description: "",
            buttonTitle: "Pay with Sofinco",
            icon: "Sofinco"
          };
          case "SPLITIT": return {
            name: "Splitit",
            description: "",
            buttonTitle: "Pay with Splitit",
            icon: "Splitit"
          };
          case "TABBY": return {
            name: "Tabby",
            description: "",
            buttonTitle: "Pay with Tabby",
            icon: "Tabby"
          };
          case "TAMARA": return {
            name: "Tamara",
            description: "",
            buttonTitle: "Pay with Tamara",
            icon: "Tamara"
          };
          case "TENDOPAY": return {
            name: "Tendopay",
            description: "",
            buttonTitle: "Pay with Tendopay",
            icon: "Tendopay"
          };
          case "TOUCHNGO": return {
            name: "Touch'n Go",
            description: "",
            buttonTitle: "Pay with Touch'n Go",
            icon: "Touchngo"
          };
          case "TRIPLEA": return {
            name: "Triple-A",
            description: "",
            buttonTitle: "Pay with Triple-A",
            icon: "Triplea"
          };
          case "TRUEMONEY": return {
            name: "TrueMoney",
            description: "",
            buttonTitle: "Pay with TrueMoney",
            icon: "Truemoney"
          };
          case "TWISTO": return {
            name: "Twisto",
            description: "",
            buttonTitle: "Pay with Twisto",
            icon: "Twisto"
          };
          case "UPLIFT": return {
            name: "Uplift",
            description: "",
            buttonTitle: "Pay with Uplift",
            icon: "Uplift"
          };
          case "VALU": return {
            name: "ValU",
            description: "",
            buttonTitle: "Pay with ValU",
            icon: "Valu"
          };
          case "ZEST": return {
            name: "Zest Money",
            description: "",
            buttonTitle: "Pay with Zest Money",
            icon: "Zest"
          };
          case "ZIP": return {
            name: "Zip",
            description: "",
            buttonTitle: "Pay with Zip",
            icon: "Zip"
          };
          case "ZOODPAY": return {
            name: "Zoodpay",
            description: "",
            buttonTitle: "Pay with Zoodpay",
            icon: "Zoodpay"
          };
          case "BKASH": return {
            name: "bKash",
            description: "",
            buttonTitle: "Pay with bKash",
            icon: "Bkash"
          };
          case "ERATY": return {
            name: "eRaty",
            description: "",
            buttonTitle: "Pay with eRaty",
            icon: "Eraty"
          }
        }
      },
    };
  },
);
