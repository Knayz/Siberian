<?php

use Siberian\Account;
use Siberian\Exception;
use Siberian\Hook;

/**
 * Class Customer_Mobile_Account_EditController
 */
class Customer_Mobile_Account_EditController extends Application_Controller_Mobile_Default
{
    /**
     * @throws Zend_Session_Exception
     * @throws \rock\sanitize\SanitizeException
     */
    public function findAction()
    {

        $customer = $this->getSession()->getCustomer();
        $payload = [];
        $payload['is_logged_in'] = false;

        if ($customer->getId()) {
            $metadatas = $customer->getMetadatas();
            if (empty($metadatas)) {
                $metadatas = json_decode("{}"); // we really need a javascript object here
            }

            //hide stripe customer id for secure purpose
            if ($metadatas->stripe && array_key_exists("customerId", $metadatas->stripe) && $metadatas->stripe["customerId"]) {
                unset($metadatas->stripe["customerId"]);
            }

            $birthdate = new Zend_Date();
            $birthdate->setTimestamp($customer->getBirthdate());

            $payload = [
                "id" => $customer->getId(),
                "civility" => $customer->getCivility(),
                "firstname" => $customer->getFirstname(),
                "lastname" => $customer->getLastname(),
                "nickname" => $customer->getNickname(),
                "birthdate" => $birthdate->toString('dd/MM/y'),
                "email" => $customer->getEmail(),
                "show_in_social_gaming" => (bool)$customer->getShowInSocialGaming(),
                "is_custom_image" => (bool)$customer->getIsCustomImage(),
                "metadatas" => $metadatas
            ];

            if (Siberian_CustomerInformation::isRegistered("stripe")) {
                $exporter_class = Siberian_CustomerInformation::getClass("stripe");
                if (class_exists($exporter_class) && method_exists($exporter_class, "getInformation")) {
                    $tmp_class = new $exporter_class();
                    $info = $tmp_class->getInformation($customer->getId());
                    $payload["stripe"] = $info ? $info : [];
                }
            }

            $payload["is_logged_in"] = true;
            $payload["isLoggedIn"] = true;

        }

        $this->_sendJson($payload);

    }

    /**
     *
     */
    public function postAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getBodyParams();
            $session = $this->getSession();
            $customer = $session->getCustomer();
            $application = $this->getApplication();
            $appId = $application->getId();

            if (!$customer->getId()) {
                throw new Exception(p__('customer', "The profile you are trying to edit doesn't exists!"));
            }

            if (!Zend_Validate::is($data['email'], 'EmailAddress')) {
                throw new Exception(p__('customer', 'The e-mail you used is not valid!'));
            }

            $dummy = new Customer_Model_Customer();
            $dummy->find([
                'email' => $data['email'],
                'app_id' => $appId
            ]);

            if ($dummy->getId() &&
                $dummy->getId() !== $customer->getId()) {
                throw new Exception(p__('customer', 'This e-mail address is already in use, maybe you want to retrieve your password?'));
            }

            if (!empty($data['nickname'])) {
                $validFormat = preg_match('/^[\w]{6,30}$/', $data['nickname']);
                if (!$validFormat) {
                    throw new Exception(p__('customer', 'The nickname must contains only letters, numbers & underscore and be 6 to 30 characters long.'));
                }

                $dummy = (new Customer_Model_Customer())->find([
                    'nickname' => $data['nickname'],
                    'app_id' => $appId
                ]);

                if ($dummy &&
                    $dummy->getId() &&
                    $dummy->getId() !== $customer->getId()) {
                    throw new Exception(p__('customer', 'This nickname is already used, please choose another one!'));
                }
            }

            if (empty($data['show_in_social_gaming'])) {
                $data['show_in_social_gaming'] = 0;
            }

            if (isset($data['id'])) {
                unset($data['id']);
            }
            if (isset($data['customer_id'])) {
                unset($data['customer_id']);
            }

            if (isset($data['birthdate'])) {
                $birthdate = new Zend_Date();
                $birthdate->setDate($data['birthdate'], 'DD/MM/YYYY');
                $data['birthdate'] = $birthdate->getTimestamp();
            }

            $customer->saveImage($data['image']);
            unset($data['image']);

            $password = '';
            $data['change_password'] = filter_var($data['change_password'], FILTER_VALIDATE_BOOLEAN);
            if ($data['change_password'] === true &&
                !empty($data['password'])) {

                if (empty($data['old_password']) ||
                    (!empty($data['old_password']) &&
                        !$customer->isSamePassword($data['old_password']))) {
                    throw new Exception(p__('customer', 'The current password is incorrect.'));
                }

                $password = $data['password'];
            }

            $customer->setData($data);
            if (!empty($password)) {
                $customer->setPassword($password);
                Hook::trigger('mobile.customer.changePassword.success', [
                    'appId' => $this->getApplication()->getId(),
                    'customerId' => $customer->getId(),
                    'customer' => $customer,
                    'newPassword' => $password,
                    'token' => Zend_Session::getId(),
                    'type' => 'account'
                ]);

            }
            if (!empty($data['metadatas'])) {
                $customer->setMetadatas($data['metadatas']);
            }

            // New mobile account hooks/forms
            if (array_key_exists('extendedFields', $data)) {
                Account::saveFields([
                    'application' => $this->getApplication(),
                    'request' => $this->getRequest(),
                    'session' => $this->getSession(),
                ], $data['extendedFields']);
            }

            $customer->save();


            $currentCustomer = Customer_Model_Customer::getCurrent();

            $currentCustomer['extendedFields'] = Account::getFields([
                'application' => $this->getApplication(),
                'request' => $this->getRequest(),
                'session' => $this->getSession(),
            ]);

            $payload = [
                'success' => true,
                'message' => p__('customer', 'Settings saved!'),
                'customer' => $currentCustomer
            ];

        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    public function sendTestPushAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getBodyParams();

            if (empty($data)) {
                throw new Exception(p__('customer', 'Missing data!'));
            }

            $tokens = [
                $data['deviceToken']
            ];

            // Adds a little delay!
            sleep(1);

            $push = \Push\Model\StandalonePush::buildFromTokens($tokens);
            $push->sendMessage(
                p__('customer', 'Test push'),
                p__('customer', 'This is a test push!'),
                '',
                null,
                null,
                null,
                false
            );

            $payload = [
                'success' => true,
                'message' => p__('customer', 'Test push sent!'),
            ];
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    public function saveSettingsAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getBodyParams();
            $application = $this->getApplication();
            $appId = $application->getId();

            if (empty($data)) {
                throw new Exception(p__('customer', 'Missing data!'));
            }

            switch ((int) $data['deviceType']) {
                case 1: // Android
                    $device = (new Push_Model_Android_Device())->find(
                        ['device_uid' => $data['deviceUid'], 'app_id' => $appId]
                    );
                    if ($device && $device->getId()) {
                        $device->setPushAlert(filter_var($data['push'], FILTER_VALIDATE_BOOLEAN) ?
                            'enabled' : 'disabled');
                        $device->save();
                    }
                    break;
                case 2: // iOS
                    $device = (new Push_Model_Iphone_Device())->find(
                        ['device_uid' => $data['deviceUid'], 'app_id' => $appId]
                    );
                    if ($device && $device->getId()) {
                        $device->setPushAlert(filter_var($data['push'], FILTER_VALIDATE_BOOLEAN) ?
                            'enabled' : 'disabled');
                        $device->save();
                    }
                    break;
                case 3:
                    // Browser, for later!
                    break;
                default:
                    // Nope!
            }

            $payload = [
                'success' => true,
                'message' => p__('customer', 'Settings saved!'),
            ];
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

}
