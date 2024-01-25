define(
  [
    'payfurljs',
    'Payfurl_Payment/js/model/payfurl-configuration',
  ],
  function(pf, payfurlConfig) {
    'use strict';

    return pf.init(payfurlConfig.getEnv(), payfurlConfig.getPublicKey(), payfurlConfig.isDebug());
  },
);
