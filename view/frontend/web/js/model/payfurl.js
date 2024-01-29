define(
  [
    'payfurljs',
    'Payfurl_Payment/js/model/payfurl-configuration'
  ],
  function(pf, payfurlConfig) {
    'use strict';

    return pf.init(payfurlConfig.getEnv(), payfurlConfig.getPublicKey(), payfurlConfig.isDebug());
    //return pf.init('local', 'pubtest50b8ba70da2b04e471b50a88c7ffeddda9452e2004-au', payfurlConfig.isDebug());
  },
);
