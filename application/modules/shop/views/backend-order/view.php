<?php

use app\backend\widgets\BackendWidget;
use kartik\editable\Editable;
use yii\helpers\Html;
use kartik\dynagrid\DynaGrid;

/**
 * @var $this yii\web\View
 * @var $managers array
 * @var $model \app\modules\shop\models\Order
 * @var $transactionsDataProvider \yii\data\ArrayDataProvider
 * @var \app\backend\models\OrderChat $message
 */

$this->title = Yii::t('app', 'Order #{id}', ['id' => $model->id]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Orders'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$items = [];
foreach ($model->items as $item) {
    if (isset($items[$item->parent_id])) {
        $items[$item->parent_id][] = $item;
    } else {
        $items[$item->parent_id] = [$item];
    }
}

?>
<?php $this->beginBlock('page-buttons'); ?>
<div class="row" style="margin-bottom: 10px;">
    <div class="col-xs-12">
    <a href="#" class="btn btn-default pull-right do-not-print" id="print-button"><?=\kartik\icons\Icon::show(
            'print'
        )?>&nbsp;&nbsp;<?=Yii::t('app', 'Print')?></a>
    <a href="<?=Yii::$app->request->get(
        'returnUrl',
        \yii\helpers\Url::toRoute(['index'])
    )?>" class="btn btn-danger pull-right do-not-print"><?=\kartik\icons\Icon::show(
            'arrow-circle-left'
        )?>&nbsp;&nbsp;<?=Yii::t('app', 'Back')?></a>
    <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success pull-right']); ?>
    </div>
</div>
<?php $this->endBlock(); ?>

<h1 class="page-title txt-color-blueDark">
    <?=Html::encode($this->title)?>
</h1>
<?php
$sum_transactions = 0;
foreach ($model->transactions as $transaction) {
    $sum_transactions += $transaction->total_sum;
}
if ($sum_transactions < $model->total_price):
    ?>
    <div class="alert alert-danger">
        <b><?=Yii::t('app', 'Warning!')?></b>
        <?=
        Yii::t(
            'app',
            'Total sum of transactions is {sum} which is lower then order\'s total price {order}',
            [
                'sum' => $sum_transactions,
                'order' => $model->total_price,
            ]
        );
        ?>
    </div>
<?php endif; ?>
<?php
    $form = \kartik\widgets\ActiveForm::begin(
        [
            'action' => ['', 'id' => $model->id],
            'method' => 'post',
            'type' => \kartik\form\ActiveForm::TYPE_HORIZONTAL,
            'options' => [
                'class' => 'form-order-backend',
            ],
        ]
    );
    echo $this->blocks['page-buttons'];
?>
<div class="row">
    <div class="col-xs-6">
        <?php
        BackendWidget::begin(
            [
                'icon' => 'info-circle',
                'title' => Yii::t('app', 'Order information'),
            ]
        );
        ?>
        <table class="table table-striped table-bordered">
            <tbody>
            <tr>
                <th><?=$model->getAttributeLabel('user')?></th>
                <td>
                    <?=
                    !is_null($model->user) ? $model->user->username : Html::tag('em', Yii::t('yii', '(not set)'))
                    ?>
                </td>
            </tr>
            <tr>
                <th><?=$model->getAttributeLabel('manager')?></th>
                <td>
                    <?=
                    Editable::widget(
                        [
                            'attribute' => 'manager_id',
                            'data' => $managers,
                            'displayValue' => !is_null(
                                $model->manager
                            ) ? $model->manager->username : Html::tag('em', Yii::t('yii', '(not set)')),
                            'formOptions' => [
                                'action' => ['change-manager', 'id' => $model->id],
                            ],
                            'inputType' => Editable::INPUT_DROPDOWN_LIST,
                            'model' => $model,
                        ]
                    )
                    ?>
                </td>
            </tr>
            <tr>
                <th><?=$model->getAttributeLabel('start_date')?></th>
                <td><?=$model->start_date?></td>
            </tr>
            <tr>
                <th><?=$model->getAttributeLabel('end_date')?></th>
                <td><?=$model->end_date?></td>
            </tr>
            <tr>
                <th><?=$model->getAttributeLabel('order_stage_id')?></th>
                <td>
                    <?=
                    Editable::widget(
                        [
                            'attribute' => 'order_stage_id',
                            'data' => \app\components\Helper::getModelMap(
                                \app\modules\shop\models\OrderStage::className(),
                                'id',
                                'name_short'
                            ),
                            'displayValue' => $model->stage !== null ? Html::tag(
                                'span',
                                $model->stage->name_short
                            ) : Html::tag('em', Yii::t('yii', '(not set)')),
                            'formOptions' => [
                                'action' => ['update-stage', 'id' => $model->id],
                            ],
                            'inputType' => Editable::INPUT_DROPDOWN_LIST,
                            'model' => $model,
                        ]
                    )
                    ?>
                </td>
            </tr>
            <tr>
                <th><?=$model->getAttributeLabel('payment_type_id')?></th>
                <td>
                    <?=
                    Editable::widget(
                        [
                            'attribute' => 'payment_type_id',
                            'data' => \app\components\Helper::getModelMap(
                                \app\modules\shop\models\PaymentType::className(),
                                'id',
                                'name'
                            ),
                            'displayValue' => $model->paymentType !== null ? Html::tag(
                                'span',
                                $model->paymentType->name
                            ) : Html::tag('em', Yii::t('yii', '(not set)')),
                            'formOptions' => [
                                'action' => ['update-payment-type', 'id' => $model->id],
                            ],
                            'inputType' => Editable::INPUT_DROPDOWN_LIST,
                            'model' => $model,
                        ]
                    )
                    ?>
                </td>
            </tr>
            <?php foreach ($model->abstractModel->attributes as $name => $attribute): ?>
                <tr>
                    <th><?=$model->abstractModel->getAttributeLabel($name)?></th>
                    <td>
                        <button data-toggle="modal" data-target="#custom-fields-modal" class="kv-editable-value kv-editable-link">
                            <?=
                            !empty($attribute)
                                ? Html::encode($attribute)
                                : Html::tag('em', Yii::t('yii', '(not set)'))
                            ?>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php BackendWidget::end(); ?>

        <?php
            BackendWidget::begin(
                [
                    'icon' => 'user',
                    'title' => Yii::t('app', 'Customer'),
                ]
            );
            /** @var \app\modules\shop\models\Customer $customer */
            $customer = $model->customer;
            echo $form->field($customer, 'first_name');
            echo $form->field($customer, 'middle_name');
            echo $form->field($customer, 'last_name');
            echo $form->field($customer, 'email');
            echo $form->field($customer, 'phone');
            /** @var \app\properties\AbstractModel $abstractModel */
            $abstractModel = $customer->getAbstractModel();
            $abstractModel->setArrayMode(false);
            foreach ($abstractModel->attributes() as $attr) {
                echo $form->field($abstractModel, $attr);
            }
        ?>
        <?php BackendWidget::end(); ?>

        <?php
            BackendWidget::begin(
                [
                    'icon' => 'user',
                    'title' => Yii::t('app', 'Contragent'),
                ]
            );
            $contragents = array_reduce($customer->contragents,
                function ($result, $item) use ($customer)
                {
                    /** @var \app\modules\shop\models\Contragent $item */
                    $result[$item->id] = $item;
                    return $result;
                }, [0 => \app\modules\shop\models\Contragent::createEmptyContragent($customer)]
            );

            echo $form->field($model, 'contragent_id')->dropDownList(array_reduce($contragents,
                function ($result, $item)
                {
                    /** @var \app\modules\shop\models\Contragent $item */
                    if ($item->isNewRecord) {
                        $result[0] = 'Новый Контрагент';
                    } else {
                        $result[$item->id] = $item->type;
                    }
                    return $result;
                }, [])
            , ['class' => 'contragents']);
        ?>
        <hr />
        <div class="contragents_list">
            <?php
                foreach ($contragents as $key => $contragent) {
                    /** @var \app\modules\shop\models\Contragent $contragent */
                    $_content = $form->field($contragent, 'type')
                        ->dropDownList(['Individual' => 'Individual', 'Self-employed' => 'Self-employed', 'Legal entity' => 'Legal entity']);
                    /** @var \app\properties\AbstractModel $abstractModel */
                    $abstractModel = $contragent->getAbstractModel();
                    $abstractModel->setArrayMode(false);
                    foreach ($abstractModel->attributes() as $attr) {
                        $_content .= $form->field($abstractModel, $attr);
                    }

                    $_content .= Html::tag('h5', Yii::t('app', 'Delivery information'));
                    $deliveryInformation = !empty($contragent->deliveryInformation)
                        ? $contragent->deliveryInformation
                        : ($contragent->isNewRecord
                            ? \app\modules\shop\models\DeliveryInformation::createNewDeliveryInformation($contragent)
                            : \app\modules\shop\models\DeliveryInformation::createNewDeliveryInformation($contragent, false)
                        );
                    $_content .= $form->field($deliveryInformation, 'country_id')
                        ->dropDownList(\app\components\Helper::getModelMap(\app\models\Country::className(), 'id', 'name'));
                    $_content .= $form->field($deliveryInformation, 'city_id')
                        ->dropDownList(\app\components\Helper::getModelMap(\app\models\City::className(), 'id', 'name'));
                    $_content .= $form->field($deliveryInformation, 'zip_code');
                    $_content .= $form->field($deliveryInformation, 'address');

                    echo Html::tag('div', $_content, [
                        'class' => "contragent contragent_$key" . ($key === intval($model->contragent_id) ? '' : ' hide')
                    ]);
                }
            ?>
        </div>
        <?php BackendWidget::end(); ?>

        <?php
            BackendWidget::begin(
                [
                    'icon' => 'user',
                    'title' => Yii::t('app', 'Order delivery information'),
                ]
            );

            $orderDeliveryInformation = $model->orderDeliveryInformation;
            echo $form->field($orderDeliveryInformation, 'shipping_option_id')->dropDownList(\app\components\Helper::getModelMap(\app\modules\shop\models\ShippingOption::className(), 'id', 'name'));
            echo $form->field($orderDeliveryInformation, 'shipping_price');
            echo $form->field($orderDeliveryInformation, 'shipping_price_total');
            echo $form->field($orderDeliveryInformation, 'planned_delivery_date');
            echo $form->field($orderDeliveryInformation, 'planned_delivery_time');
            echo $form->field($orderDeliveryInformation, 'planned_delivery_time_range');
            /** @var \app\properties\AbstractModel $abstractModel */
            $abstractModel = $orderDeliveryInformation->getAbstractModel();
            $abstractModel->setArrayMode(false);
            foreach ($abstractModel->attributes() as $attr) {
                echo $form->field($abstractModel, $attr);
            }
        ?>
        <?php BackendWidget::end(); ?>
    </div>
    <div class="col-xs-6 order-chat">
        <?php
        BackendWidget::begin(
            [
                'icon' => 'comments',
                'title' => Yii::t('app', 'Managers chat'),
            ]
        );
        ?>
        <div class="widget-body widget-hide-overflow no-padding">
            <div id="chat-body" class="chat-body custom-scroll">
                <ul>
                    <?php foreach ($lastMessages as $msg): ?>
                        <li class="message">
                            <?php if (!is_null($msg->user)): ?>
                                <img src="<?=$msg->user->gravatar()?>" class="online" alt="">
                            <?php endif; ?>
                            <div class="message-text">
                                <time>
                                    <?=$msg->date?>
                                </time>
                                <a href="javascript:void(0);" class="username"><?=
                                    !is_null($msg->user) ? $msg->user->username : Yii::t('app', 'Unknown')
                                    ?></a>
                                <?=nl2br(Html::encode($msg->message))?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="chat-footer">
                <div class="textarea-div">
                    <div class="typearea">
                        <?= Html::textarea('order-chat-message', $message->message, ['class' => 'custom-scroll']); ?>
                    </div>
                </div>
                <span class="textarea-controls">
                    <?=
                    Html::submitButton(
                        Yii::t('app', 'Submit'),
                        ['class' => 'btn btn-sm btn-primary pull-right']
                    )
                    ?>
                </span>
            </div>
            <?php BackendWidget::end(); ?>
        </div>

        <div class="order-view">
            <?php
            BackendWidget::begin(
                [
                    'icon' => 'list-alt',
                    'title' => Yii::t('app', 'Order items'),
                ]
            );
            ?>
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th><?=Yii::t('app', 'Name')?></th>
                    <th><?=Yii::t('app', 'Price')?></th>
                    <th><?=Yii::t('app', 'Quantity')?></th>
                    <th><?=Yii::t('app', 'Price sum')?></th>
                    <th style="width: 43px;"></th>
                </tr>
                </thead>
                <tbody>
                <?php if (isset($items[0])): ?>
                    <?= $this->render('items', ['allItems' => $items, 'items' => $items[0]]) ?>
                <?php endif; ?>
                <?php if (isset($model->shippingOption)): ?>
                    <tr>
                        <td colspan="3"><?=Html::encode($model->shippingOption->name)?></td>
                        <td colspan="2"><?=Yii::$app->formatter->asDecimal($model->shippingOption->cost, 2)?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <th colspan="2"><?=Yii::t('app', 'Summary')?></th>
                    <th><?=$model->items_count?></th>
                    <th colspan="2"><?=Yii::$app->formatter->asDecimal($model->total_price, 2)?></th>
                </tr>
                </tbody>
            </table>
            <div class="do-not-print">
                <br />

                <div class="row">
                    <div class="col-xs-6">
                        <label for="add-product"><?=Yii::t('app', 'Add a new product to order')?></label>
                    </div>
                </div>
                <div class="row form-inline">
                    <div class="col-xs-12 form-group">
                        <?=
                        Html::dropDownList(
                            'parentId',
                            [],
                            ['0' => Yii::t('app', 'Select a parent order item')] + \yii\helpers\ArrayHelper::map(
                                $model->items,
                                'id',
                                function($element) {
                                    return !is_null($element->product)
                                        ? $element->product->name :
                                        Yii::t('app', 'Product not found');
                                }
                            ),
                            [
                                'class' => 'form-control input-group-addon col-xs-3',
                                'id' => 'add-product-parent',
                            ]
                        )
                        ?>
                        <?=
                        \app\widgets\AutoCompleteSearch::widget(
                            [
                                'options' => [
                                    'class' => 'form-control col-xs-3',
                                ],
                                'id' => 'add-product',
                                'name' => 'add-product',
                                'route' => ['auto-complete-search', 'orderId' => $model->id],
                            ]
                        )
                        ?>
                    </div>
                </div>
            </div>
            <?php BackendWidget::end(); ?>

            <?php
            BackendWidget::begin(
                [
                    'icon' => 'dollar',
                    'title' => Yii::t('app', 'Order transactions'),
                ]
            );
            ?>

            <?=
            DynaGrid::widget(
                [
                    'options' => [
                        'id' => 'transactions-grid',
                    ],
                    'theme' => 'panel-default',
                    'gridOptions' => [
                        'dataProvider' => $transactionsDataProvider,
                        'hover' => true,
                        'panel' => false
                    ],
                    'columns' => [
                        'id',
                        [
                            'attribute' => 'status',
                            'value' => function ($model, $key, $index, $column) {
                                /** @var \app\modules\shop\models\OrderTransaction $model */
                                return $model->getTransactionStatus();
                            },
                        ],
                        'start_date',
                        'end_date',
                        'total_sum',
                        [
                            'attribute' => 'payment_type_id',
                            'filter' => \app\components\Helper::getModelMap(
                                \app\modules\shop\models\PaymentType::className(),
                                'id',
                                'name'
                            ),
                            'value' => function ($model, $key, $index, $column) {
                                if ($model === null || $model->paymentType === null) {
                                    return null;
                                }
                                return $model->paymentType->name;
                            },
                        ],

                    ],
                ]
            );
            ?>
            <?php BackendWidget::end(); ?>
        </div>
    </div>
</div>
<?php
echo $this->blocks['page-buttons'];
    $form->end();
?>

<?php \yii\bootstrap\Modal::begin(
    ['id' => 'custom-fields-modal', 'header' => Yii::t('app', 'Edit order properties')]
) ?>
<?php $form = \kartik\widgets\ActiveForm::begin(['action' => ['update-order-properties', 'id' => $model->id]]) ?>
<?php foreach (\app\models\PropertyGroup::getForModel($model->object->id, $model->id) as $group): ?>
    <?php if ($group->hidden_group_title == 0): ?>
        <h4><?=$group->name;?></h4>
    <?php endif; ?>
    <?php $properties = \app\models\Property::getForGroupId($group->id); ?>
    <?php foreach ($properties as $property): ?>
        <?=$property->handler($form, $model->abstractModel, [], 'frontend_edit_view');?>
    <?php endforeach; ?>
<?php endforeach; ?>
<?=Html::submitButton(Yii::t('app', 'Send'), ['class' => 'btn btn-primary'])?>
<?php \kartik\widgets\ActiveForm::end() ?>
<?php \yii\bootstrap\Modal::end() ?>

<?php

$js = <<<JS
    "use strict";
    $('#orderchat-message').keypress(function (event) {
        if (event.keyCode == 10) {
            $(this).parents('form').eq(0).submit();
        }
    });
    $('body').on('editableSuccess', function () {
        location.reload();
    });
    $('#print-button').click(function () {
        window.print();
        return false;
    });
    jQuery('#add-product-parent').change(function() {
        var parentId = jQuery(this).val();
        jQuery('#add-product').autocomplete('option', 'source', '/shop/backend-order/auto-complete-search?orderId={$model->id}&parentId=' + parentId);
    });
    $('select.contragents').change(function(event) {
        $('.contragents_list .contragent').addClass('hide');
        $('.contragents_list .contragent_'+$(this).val()).removeClass('hide');
    });
    $('form.form-order-backend').submit(function(event) {
        $('.contragents_list .contragent.hide').remove();
    });
JS;
$this->registerJs($js);

?>
