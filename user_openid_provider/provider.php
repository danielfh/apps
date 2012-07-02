<?php

require_once('Zend/OpenId/Provider.php');
OCP\App::checkAppEnabled('user_openid_provider');

if (!isset($_REQUEST['openid_mode'])) {
	OC_Template::printGuestPage('user_openid_provider', 'main');
	die;
}

$session = new OC_OpenIdProviderUserSession();
$storage = new OC_OpenIdProviderStorage();
$server = new Zend_OpenId_Provider(null, null, $session, $storage);

if (OCP\User::isLoggedIn() and !$session->getLoggedInUser()) {
	$session->setLoggedInUser(OCP\Util::linkToAbsolute('', '?').OCP\User::getUser());
}

if (isset($_GET['openid_action']) and $_GET['openid_action']=='login') {
	unset($_GET['openid_action']);
	$params = '?'.Zend_OpenId::paramsToQuery($_GET);
	$next = OCP\Util::linkToRemote('openid_provider') . $params;
	$loginPage = OCP\Util::linkToAbsolute( '', 'index.php' ).'?redirect_url='
		.urlencode($next);
	header('Location: '.$loginPage );
} else if (isset($_GET['openid_action']) and $_GET['openid_action'] == 'trust') {
	OCP\User::checkLoggedIn();
	if (isset($_POST['allow'])) {
		$server->respondToConsumer($_GET);
	} else {
		$tmpl = new OCP\Template( 'user_openid_provider', 'trust', 'user');
		$tmpl->assign('site', $server->getSiteRoot($_GET));
		$tmpl->assign('openid', $server->getLoggedInUser());
		$tmpl->printPage();
	}
} else {
	$ret = $server->handle();
	if (is_string($ret)) {
		echo $ret;
	} else if ($ret !== true) {
		header('HTTP/1.0 403 Forbidden');
		echo 'Forbidden';
	}
}