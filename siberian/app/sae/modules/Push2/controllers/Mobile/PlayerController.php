<?php

namespace Push2\Mobile;

use Push2\Model\Onesignal\Player;
use \Application_Controller_Mobile_Default as MobileController;

/**
 * Class PlayerController
 */
class PlayerController extends MobileController
{
    // from Push2.registerPlayer factory
    public function registerAction()
    {
        try {
            $application = $this->getApplication();
            $option = $this->getCurrentOptionValue();
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $data = $request->getBodyParams();

            $player_id = $data['player_id'];
            $player = (new Player())->find($player_id, 'player_id');
            $player->setAppId($application->getId());
            $player->setAppName($application->getName());
            $player->setPlayerId($data['player_id']);
            $player->setPushToken($data['push_token']);
            $player->setExternalUserId($data['external_user_id']);
            $player->setCustomerId($customerId);
            $player->save();

            $payload = [
                'success' => true,
                'message' => p__('push2', 'Player successfully registered'),
            ];
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

}

// @important!
class_alias(PlayerController::class, 'Push2_Mobile_PlayerController');