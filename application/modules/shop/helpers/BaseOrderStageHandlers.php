<?php

namespace app\modules\shop\helpers;

use app\modules\shop\events\OrderStageEvent;
use app\modules\shop\events\OrderStageLeafEvent;
use app\modules\shop\models\Contragent;
use app\modules\shop\models\Customer;
use app\modules\shop\models\DeliveryInformation;
use app\modules\shop\models\Order;
use app\modules\shop\models\OrderDeliveryInformation;
use app\modules\shop\models\OrderTransaction;
use app\modules\shop\models\PaymentType;

class BaseOrderStageHandlers
{
    /**
     * @param $event OrderStageLeafEvent
     */
    public static function handleCustomer(OrderStageLeafEvent $event)
    {
        $event->setStatus(false);

        if (\Yii::$app->request->isPost) {
            $order = Order::getOrder();
            if (empty($order) || !$order->load(\Yii::$app->request->post())) {
                return null;
            }
            /** @var Customer $customer */
            $customer = !empty($order->customer)
                ? $order->customer
                : (!empty(Customer::getCustomerByUserId($order->user_id))
                    ? Customer::getCustomerByUserId($order->user_id)
                    : Customer::createEmptyCustomer($order->user_id)
                );

            $data = \Yii::$app->request->post();
            $isNewCustomer = $customer->isNewRecord;
            if ($customer->load(\Yii::$app->request->post()) && $customer->save()) {
                if ($isNewCustomer && !empty($customer->getPropertyGroup())) {
                    $customer->getPropertyGroup()->appendToObjectModel($customer);
                    $data[$customer->getAbstractModel()->formName()] = isset($data['CustomerNew']) ? $data['CustomerNew'] : [];
                }
                $customer->saveModelWithProperties($data);
            } else {
                return null;
            }

            /** @var Contragent $contragent */
            $contragent = !empty($order->contragent)
                ? $order->contragent
                : Contragent::createEmptyContragent($customer);

            $isNewContragent = $contragent->isNewRecord;
            if ($contragent->load(\Yii::$app->request->post()) && $contragent->save()) {
                if ($isNewContragent && !empty($contragent->getPropertyGroup())) {
                    $contragent->getPropertyGroup()->appendToObjectModel($contragent);
                    $data[$contragent->getAbstractModel()->formName()] = isset($data['ContragentNew']) ? $data['ContragentNew'] : [];
                }
                $contragent->saveModelWithProperties($data);
            } else {
                return null;
            }

            $order->customer_id = $customer->id;
            $order->contragent_id = $contragent->id;
            if ($order->save()) {
                $event->setStatus(true);
            }
        }
    }

    /**
     * @param $event OrderStageLeafEvent
     */
    public static function handlePayment(OrderStageLeafEvent $event)
    {
        $event->setStatus(false);

        if (\Yii::$app->request->isPost) {
            $order = Order::getOrder();
            if (empty($order)) {
                return null;
            }

            /** @var PaymentType $paymentType */
            $paymentType = PaymentType::findOne(['id' => \Yii::$app->request->post('PaymentType'), 'active' => 1]);
            if (empty($paymentType)) {
                return null;
            }

            $order->payment_type_id = $paymentType->id;
            if ($order->save()) {
                $event->setStatus(true);
            }
        }
    }

    /**
     * @param OrderStageLeafEvent $event
     */
    public static function handlePaymentPay(OrderStageLeafEvent $event)
    {
        $event->setStatus(false);
    }

    /**
     * @param $event OrderStageLeafEvent
     */
    public static function handleDelivery(OrderStageLeafEvent $event)
    {
        $event->setStatus(false);

        if (\Yii::$app->request->isPost) {
            $order = Order::getOrder();
            if (empty($order)) {
                return null;
            }
            /** @var DeliveryInformation $deliveryInformation */
            $deliveryInformation = empty($order->contragent) ? null :
                ( empty($order->contragent->deliveryInformation) ? DeliveryInformation::createNewDeliveryInformation($order->contragent) : $order->contragent->deliveryInformation );
            /** @var OrderDeliveryInformation $orderDeliveryInformation */
            $orderDeliveryInformation = empty($order->orderDeliveryInformation) ? OrderDeliveryInformation::createNewOrderDeliveryInformation($order) : $order->orderDeliveryInformation;
            if (empty($deliveryInformation) || empty($orderDeliveryInformation)) {
                return null;
            }

            if ($deliveryInformation->load(\Yii::$app->request->post())) {
                if ($deliveryInformation->save()) {
                    $data = \Yii::$app->request->post();
                    $isNewOrderDeliveryInformation = $orderDeliveryInformation->isNewRecord;
                    $orderDeliveryInformation->load(\Yii::$app->request->post());
                    if ($orderDeliveryInformation->save()) {
                        if ($isNewOrderDeliveryInformation) {
                            $orderDeliveryInformation->addPropertyGroup($orderDeliveryInformation->getPropertyGroup()->id);
                            $data[$orderDeliveryInformation->getAbstractModel()->formName()] = isset($data['OrderDeliveryInformationNew']) ? $data['OrderDeliveryInformationNew'] : [];
                        }
                        $orderDeliveryInformation->saveProperties($data);
                        $orderDeliveryInformation->invalidateTags();

                        $event->setStatus(true);
                    }
                }
            }
        }
    }

    public static function handleStageCustomer(OrderStageEvent $event)
    {
        $order = $event->eventData()['order'];
        /** @var Customer $customer */
        $customer = !empty($order->customer)
            ? $order->customer
            : (!empty(Customer::getCustomerByUserId($order->user_id))
                ? Customer::getCustomerByUserId($order->user_id)
                : Customer::createEmptyCustomer($order->user_id)
            );

        /** @var Contragent[] $contragents */
        $contragents = array_reduce($customer->contragents,
            function ($result, $item) use ($customer)
            {
                /** @var \app\modules\shop\models\Contragent $item */
                $result[$item->id] = $item;
                return $result;
            }, [0 => \app\modules\shop\models\Contragent::createEmptyContragent($customer)]
        );

        $event->addEventData([
            'user_id' => $order->user_id,
            'customer' => $customer,
            'contragents' => $contragents,
        ]);
    }

    public static function handleStagePayment(OrderStageEvent $event)
    {
        /** @var Order $order */
        $order = $event->eventData()['order'];
        $order->calculate();

        /** @var PaymentType[] $paymentTypes */
        $paymentTypes = PaymentType::getPaymentTypes();

        $event->addEventData([
            'paymentTypes' => $paymentTypes,
            'totalPayment' => $order->total_price,
        ]);
    }

    public static function handleStageDelivery(OrderStageEvent $event)
    {
        /** @var Order $order */
        $order = $event->eventData()['order'];
        /** @var Contragent $contragent */
        $contragent = $order->contragent;

        /** @var DeliveryInformation $deliveryInformation */
        $deliveryInformation = !empty($contragent->deliveryInformation)
            ? $contragent->deliveryInformation
            : DeliveryInformation::createNewDeliveryInformation($contragent);

        /** @var OrderDeliveryInformation $orderDeliveryInformation */
        $orderDeliveryInformation = !empty($order->orderDeliveryInformation)
            ? $order->orderDeliveryInformation
            : OrderDeliveryInformation::createNewOrderDeliveryInformation($order);

        $event->addEventData([
            'deliveryInformation' => $deliveryInformation,
            'orderDeliveryInformation' => $orderDeliveryInformation,
        ]);
    }

    public static function handleStagePaymentPay(OrderStageEvent $event)
    {
        /** @var Order $order */
        $order = $event->eventData()['order'];

        $paymentType = !empty($order->paymentType) ? $order->paymentType : null;
        $orderTransaction = !empty(OrderTransaction::findLastByOrder($order))
            ? OrderTransaction::findLastByOrder($order)
            : OrderTransaction::createForOrder($order);

        $event->addEventData([
            'orderTransaction' => $orderTransaction,
            'paymentType' => $paymentType,
        ]);
    }
}
?>