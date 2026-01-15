$(document).ready(function() {

  var $form = $('form#JWTAuth');
  var $jwtEnable = $form.find('#jwt_enable');
  var $jwtSecret = $form.find('#jwt_secret');
  var $jwtIssuer = $form.find('#jwt_issuer');
  var $jwtAudience = $form.find('#jwt_audience');
  var $jwtAccessExpiration = $form.find('#jwt_access_expiration');
  var $jwtRefreshExpiration = $form.find('#jwt_refresh_expiration');


  $jwtEnable.on('change', function() {
    $jwtSecret.prop('readonly', !this.checked);
    $jwtIssuer.prop('readonly', !this.checked);
    $jwtAudience.prop('readonly', !this.checked);
    $jwtAccessExpiration.prop('readonly', !this.checked);
    $jwtRefreshExpiration.prop('readonly', !this.checked);
  }).change();
});