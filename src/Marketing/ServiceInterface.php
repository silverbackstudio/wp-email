<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Svbk\WP\Email\Marketing;

use Svbk\WP\Email\Contact;

abstract class ServiceInterface {

	abstract public function create( Contact $contact, $update = true);

	abstract public function listSubscribe( Contact $contact, $lists = array() );

	abstract public function listUnsubscribe( Contact $contact, $lists = array() );

	abstract public function updateAttributes( Contact $contact, $user_attributes = array());

	abstract public function update( Contact $contact, $attributes = array());

}
