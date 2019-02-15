<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Svbk\WP\Email\Marketing;

use Svbk\WP\Email\Contact;
use DateTime;

abstract class ServiceInterface {

	const DATE_FORMAT = DateTime::ISO8601;

	abstract public function createContact( Contact $contact );

	abstract public function listSubscribe( Contact $contact, $lists = array() );

	abstract public function listUnsubscribe( Contact $contact, $lists = array() );

	abstract public function saveContact( Contact $contact, $custom_attributes = array());

	abstract public static function formatDate( DateTime $date );

}
