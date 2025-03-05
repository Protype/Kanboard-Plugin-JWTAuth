$(document).ready(function() {

  var $form = $('form#JWTAuth');
  var $jwtEnable = $form.find('#jwt_enable');
  var $jwtSecret = $form.find('#jwt_secret');
  var $jwtIssuer = $form.find('#jwt_issuer');
  var $jwtAudience = $form.find('#jwt_audience');
  var $jwtExpiration = $form.find('#jwt_expiration');


  $jwtEnable.on('change', function() {
    $jwtSecret.prop('readonly', !this.checked);
    $jwtIssuer.prop('readonly', !this.checked);
    $jwtAudience.prop('readonly', !this.checked);
    $jwtExpiration.prop('readonly', !this.checked);
  }).change();
});