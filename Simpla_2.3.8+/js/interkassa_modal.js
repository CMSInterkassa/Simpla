$(document).ready(function () {
    $('body').prepend('<div class="blLoaderIK"><div class="loaderIK"></div></div>');
    $('.radioBtn a').on('click', function () {
        $('.blLoaderIK').css('display', 'block');
        var form = $('#ik-checkout');
        var sel = $(this).data('title');
        var tog = $(this).data('toggle');

        $('#' + tog).prop('value', sel);
        $('a[data-toggle="' + tog + '"]').not('[data-title="' + sel + '"]').removeClass('active').addClass('notActive');
        $('a[data-toggle="' + tog + '"][data-title="' + sel + '"]').removeClass('notActive').addClass('active');

        var ik_pw_via = $(this).attr('data-title');

        var payment_metod = $(this).attr('data-payment');
        if ($('input[name ="ik_pw_via"]').length > 0)
            $('input[name ="ik_pw_via"]').val(ik_pw_via);
        else
            form.append($('<input>', {type: 'hidden', name: 'ik_pw_via', val: ik_pw_via}));
        $('input[name ="payment_metod"]').val(payment_metod);
        $('.blLoaderIK').css('display', 'none');
    })
    $('.ik-payment-confirmation').click(function (e) {
        e.preventDefault();
        $('.blLoaderIK').css('display', 'block');

        var payment_metod = $('input[name ="payment_metod"]').val();
        var ik_pw_via = $('input[name ="ik_pw_via"]').val();
        var form = $('#ik-checkout');
        if (payment_metod != $(this).attr('data-payment') || ik_pw_via == '') {
            alert('Вы не выбрали валюту');
            $('.blLoaderIK').css('display', 'none');
            return;
        }
        if (ik_pw_via.search('test_interkassa|qiwi|rbk') == -1) {
            form.append(
                $('<input>', {
                    type: 'hidden',
                    name: 'ik_act',
                    val: 'process'
                }));
            form.append(
                $('<input>', {
                    type: 'hidden',
                    name: 'ik_int',
                    val: 'json'
                }));
            var url = $('#InterkassaModal').attr('data-link');

            $.ajax({
                url: url,
                type: 'POST',
                async: false,
                data: form.serialize(),
                dataType: "text",
                success: function (data) {
                    $('input[name ="ik_sign"]').val(data);
                }
            });
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                async: false,
                data: form.serialize(),
                dataType: "text",
                success: function (data) {
                    interkassa_paystart(data);
                }
            });
        }
        else {
            $('input[name="ik_act"]').remove();
            $('input[name="ik_int"]').remove();
            var url = $('#InterkassaModal').attr('data-link');
            $.ajax({
                url: url,
                type: 'POST',
                async: false,
                data: form.serialize(),
                dataType: "text",
                success: function (data) {
                    $('input[name ="ik_sign"]').val(data);
                }
            });
            form.submit();
        }
        $('.blLoaderIK').css('display', 'none');
    });
    function interkassa_paystart(data) {
        data_array = IsJsonString(data) ? JSON.parse(data) : data
        var form = $('#umi-ik-checkout');
        if (data_array['resultCode'] != 0) {
            $('input[name="ik_act"]').remove();
            $('input[name="ik_int"]').remove();
            var url = $('#InterkassaModal').attr('data-link');
            $.ajax({
                url: url,
                type: 'POST',
                async: false,
                data: form.serialize(),
                dataType: "text",
                success: function (data) {
                    $('input[name ="ik_sign"]').val(data);
                }
            });
            form.submit();
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
                $('body').append('<form method="' + data_send_form['method'] + '" id="tempformIK" action="' + data_send_form['url'] + '"></form>');
                for (var i in data_send_inputs) {
                    $('#tempformIK').append('<input type="hidden" name="' + i + '" value="' + data_send_inputs[i] + '" />');
                }
                $('#tempformIK').submit();
            }
            else {

                $('#InterkassaModal').modal('hide');
                $('#InterkassaModalButton').hide();
                if (document.getElementById('tempdivIK') == null)
                    form.after('<div id="tempdivIK">' + data_array['resultData']['internalForm'] + '</div>');
                else
                    $('#tempdivIK').html(data_array['resultData']['internalForm']);
            }
        }
    }

    function IsJsonString(str) {
        try {
            JSON.parse(str);
        } catch (e) {
            return false;
        }
        return true;
    }
});