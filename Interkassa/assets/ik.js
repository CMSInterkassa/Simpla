var ik_err_notslctcurr='Вы не выбрали валюту';
var selpayIK = {
  actForm : 'https://sci.interkassa.com/',
  req_uri : location.origin + '/payment/Interkassa/assets/callback.php',
  selPaysys : function()
	{
    if(jQuery('button.sel-ps-ik').length > 0)
      jQuery('.sel-ps-ik').click()
    else
		{
      var form = jQuery('form[name="vm_interkassa_form"]')
      form[0].action = selpayIK.actForm
      setTimeout(function(){form[0].submit()},200)
    }
  },
  paystart : function (data) {
    data_array = (this.IsJsonString(data))? JSON.parse(data) : data
    var form = jQuery('form[name="vm_interkassa_form"]');
    if (data_array['resultCode'] != 0) {
      jQuery('input[name="ik_act"]').remove();
      jQuery('input[name="ik_int"]').remove();
      jQuery('form[name="vm_interkassa_form"]').attr('action', selpayIK.actForm).submit()
    }
    else {
      if (data_array['resultData']['paymentForm'] != undefined) {
        var data_send_form = [];
        var data_send_inputs = [];
        data_send_form['url'] = data_array['resultData']['paymentForm']['action'];
        data_send_form['method'] = data_array['resultData']['paymentForm']['method'];
        for (var i in data_array['resultData']['paymentForm']['parameters']) {
          data_send_inputs[i] = data_array['resultData']['paymentForm']['parameters'][i];
        }
        jQuery('body').append('<form method="' + data_send_form['method'] + '" id="tempformIK" action="' + data_send_form['url'] + '"></form>');
        for (var i in data_send_inputs) {
          jQuery('#tempformIK').append('<input type="hidden" name="' + i + '" value="' + data_send_inputs[i] + '" />');
        }
        jQuery('#tempformIK').submit();
      }
      else {
        if (document.getElementById('tempdivIK') == null)
          jQuery('form[name="vm_interkassa_form"]').after('<div id="tempdivIK">' + data_array['resultData']['internalForm'] + '</div>');
        else
          jQuery('#tempdivIK').html(data_array['resultData']['internalForm']);
        jQuery('#internalForm').attr('action', 'javascript:selpayIK.selPaysys2()')
      }
    }
  },
  selPaysys2 : function () {
    var form2 = jQuery('#internalForm');
    var msg2 = form2.serialize();
    jQuery.ajax({
      type: 'POST',
      url: selpayIK.req_uri,
      data: msg2,
      success: function (data) {
        selpayIK.paystart2(data.responseText);
      },
      error: function (xhr, str) {
        alert('Error: ' + xhr.responseCode);
      }
    });
  },
  paystart2 : function(string){
    data_array = (this.IsJsonString(data))? JSON.parse(data) : data;
    var form2 = jQuery('#internalForm');
    if (data_array['resultCode'] != 0) {
      form2[0].action = selpayIK.actForm;
      jQuery('input[name="ik_act"]').remove();
      jQuery('input[name="ik_int"]').remove();
      jQuery('input[name="sci[ik_int]"]').remove();
      setTimeout(function(){form2[0].submit()},200)
    }
    else {
      jQuery('#tempdivIK').html('');
      if (data_array['resultData']['paymentForm'] != undefined) {
        var data_send_form = [];
        var data_send_inputs = [];
        data_send_form['url'] = data_array['resultData']['paymentForm']['action'];
        data_send_form['method'] = data_array['resultData']['paymentForm']['method'];
        for (var i in data_array['resultData']['paymentForm']['parameters']) {
          data_send_inputs[i] = data_array['resultData']['paymentForm']['parameters'][i];
        }
        jQuery('#tempdivIK').append('<form method="' + data_send_form['method'] + '" id="tempformIK2" action="' + data_send_form['url'] + '"></form>');
        for (var i in data_send_inputs) {
          jQuery('#tempformIK2').append('<input type="hidden" name="' + i + '" value="' + data_send_inputs[i] + '" />');
        }
        jQuery('#tempformIK2').submit();
      }
      else {
        jQuery('#tempdivIK').append(data_array['resultData']['internalForm']);
      }
    }
  },
  IsJsonString : function(str) {
    try {
      JSON.parse(str);
    } catch (e) {
      return false;
    }
    return true;
  }
}
jQuery(document).ready(function(){
  jQuery('body').prepend('<div class="blLoaderIK"><div class="loaderIK"></div></div>');
  jQuery('.ik_modal').on('show.bs.modal',function(event){jQuery(this).toggleClass('in');jQuery('body').toggleClass('modal-open')});
  jQuery('.ik_modal').on('hide.bs.modal',function(event){jQuery('body').toggleClass('modal-open')})
  var form=jQuery('form[name="vm_interkassa_form"]');

	jQuery('.ik-payment-confirmation').click(function(e){
		e.preventDefault();

    var pm = jQuery(this).closest('.payment_system');
    var ik_pw_via = jQuery(pm).find('.radioBtn a.active').data('title')
    if(!jQuery(pm).find('.radioBtn a').hasClass('active') ){
			alert(ik_err_notslctcurr);
			return;
		} else {
      if(ik_pw_via.search('test_interkassa|qiwi|rbk')==-1){
        var el = document.createElement('input');
        el.type='hidden',el.name='ik_act',el.value='process';
        document.getElementById('ikform').appendChild(el);
        var el2 = document.createElement('input');
        el2.type='hidden',el2.name='ik_int',el2.value='json';
        document.getElementById('ikform').appendChild(el2);
        jQuery('.blLoaderIK').css('display', 'block');
        jQuery.post(selpayIK.req_uri+'?nYg=nYa', jQuery('form[name="vm_interkassa_form"]').serialize(), function (data) {
          var a = JSON.parse(data)
          console.log(a)
          jQuery('input[name="ik_sign"]').val(a.sign);
          selpayIK.paystart(a);
          jQuery('[data-target=".ik_modal"]').hide()
          })
          .fail(function(){alert('Something wrong');})
          .always(function(){jQuery('.blLoaderIK').css('display','none');})
      }
      else jQuery('form[name="vm_interkassa_form"]').attr('action', selpayIK.actForm).submit();
		}
    jQuery('.ik_modal').modal('hide')
	});
  jQuery('.radioBtn a').on('click', function () {
    jQuery('.blLoaderIK').css('display', 'block');
    var sel = jQuery(this).data('title');
    var tog = jQuery(this).data('toggle');
    jQuery('#' + tog).prop('value', sel);
    jQuery('a[data-toggle="' + tog + '"]').not('[data-title="' + sel + '"]').removeClass('active').addClass('notActive');
    jQuery('a[data-toggle="' + tog + '"][data-title="' + sel + '"]').removeClass('notActive').addClass('active');

    var ik_pw_via = jQuery(this).attr('data-title');

    if(jQuery('input[name ="ik_pw_via"]').length>0) jQuery('input[name ="ik_pw_via"]').val(ik_pw_via);
    else jQuery('form[name="vm_interkassa_form"]').append(jQuery('<input>', {type: 'hidden', name: 'ik_pw_via', val: ik_pw_via}));

    jQuery.post(selpayIK.req_uri+'?nYg=nYs', jQuery('form[name="vm_interkassa_form"]').serialize())
      .always(function (data, status) {
        jQuery('.blLoaderIK').css('display', 'none');
        if(status == 'success'){var a = JSON.parse(data);jQuery('input[name="ik_sign"]').val(a.sign);}
        else alert('Something wrong');
      })
  })
});
