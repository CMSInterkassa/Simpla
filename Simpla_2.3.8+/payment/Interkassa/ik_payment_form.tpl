<link href="/css/interkassa.css" rel="stylesheet" type="text/css" />
<script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
<form action="<?php echo $action_url?>" method="post" id="ik-checkout">
    <input type="hidden" name="ik_co_id" value="<?= $params['ik_co_id'] ?>" />
    <input type="hidden" name="ik_am" value="<?= $params['ik_am'] ?>" />
    <input type="hidden" name="ik_pm_no" value="<?= $params['ik_pm_no'] ?>" />
    <input type="hidden" name="ik_cur" value="<?= $params['ik_cur'] ?>" />
    <input type="hidden" name="ik_desc" value="<?= $params['ik_desc'] ?>" />
    <input type="hidden" name="ik_suc_u" value="<?= $params['ik_suc_u'] ?>" />
    <input type="hidden" name="ik_fal_u" value="<?= $params['ik_fal_u'] ?>" />
    <input type="hidden" name="ik_pnd_u" value="<?= $params['ik_pnd_u'] ?>" />
    <input type="hidden" name="ik_ia_u" value="<?= $params['ik_ia_u'] ?>" />
    <?php if (isset($params['ik_pw_via']) && $params['ik_pw_via'] == 'test_interkassa_test_xts') { ?>
        <input type="hidden" name="ik_pw_via" value="<?= $params['ik_pw_via'] ?>" />
    <?php } ?>
    <?php if (is_array($params['payments_systems']) && !empty($params['payments_systems'])) { ?>
    <input type="hidden" name="payment_metod" value="" />
    <?php } ?>
    <input type="hidden" name="ik_sign" value="<?= $params['ik_sign'] ?>" />
    <div>
        <img src="<?= $path; ?>logo_interkassa.png" align="left" alt="Интеркасса" width="210px"/>
    </div>

    <div class="clearfix"></div>
    <?php if (empty($params['payments_systems'])) { ?>
    <div>
        <input type="submit" value="<?= $button_text; ?>" class="button big"/>
    </div>
    <?php } ?>

</form>

<?php	if (is_array($params['payments_systems']) && !empty($params['payments_systems'])) { ?>
<div>
    <button  id="InterkassaModalButton" class="sel-ps-ik btn btn-info btn-lg" data-toggle="modal" data-target="#InterkassaModal">Выберите метод оплаты</button>
</div>

<div class="interkasssa" style="text-align: center;">
    <div id="InterkassaModal" class="modal fade" role="dialog" data-link="<?= $callback_url.'?send_sign=1'?>">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" id="plans">
            <div class="container">
                <h3>
                    1. Выберите удобный способ оплаты<br>
                    2. Укажите валюту<br>
                    3. Нажмите &laquo;Оплатить&raquo;<br>
                </h3>
                <div class="row">
                    <?php foreach ($params['payments_systems'] as $ps => $info) { ?>
                    <div class="col-sm-3 text-center payment_system">
                        <div class="panel panel-warning panel-pricing">
                            <div class="panel-heading">
                                <div class="panel-image">
                                    <img src="<?php echo $path . $ps; ?>.png"
                                         alt="<?php echo $info['title']; ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="input-group">
                                    <div class="radioBtn btn-group">
                                        <?php foreach ($info['currency'] as $currency => $currencyAlias) { ?>
                                        <a class="btn btn-primary btn-sm notActive" href='javascript:void(0);'
                                           data-toggle="fun"
                                           data-payment ="<?= $ps;?>"
                                           data-title="<?= $currencyAlias; ?>"><?= $currency; ?></a>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <div class="panel-footer">
                                <a class="btn btn-lg btn-block btn-success ik-payment-confirmation"
                                   data-payment="<?= $ps; ?>"
                                   href="javascript:void(0);">Оплатить через<br>
                                    <strong><?= $info['title']; ?></strong>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script src="/js/interkassa_modal.js"></script>
<?php } ?>