<?php
set_time_limit(0);
//Shows a preview of the content that the user has purchased
require_once("kalturaConfig.php");
//Includes the client library and starts a Kaltura session to access the API
//More informatation about this process can be found at
//http://knowledge.kaltura.com/introduction-kaltura-client-libraries
require_once('client/KalturaClient.php');
$config = new KalturaConfiguration(PARTNER_ID);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
global $USER_ID;
$ks = $client->generateSession(ADMIN_SECRET, $USER_ID, KalturaSessionType::ADMIN, PARTNER_ID);
$client->setKs($ks);

$filter = new KalturaMetadataFilter();
$filter->metadataProfileIdEqual = PAYPAL_USER_METADATA_PROFILE_ID;
$filter->metadataObjectTypeEqual = KalturaMetadataObjectType::USER;
$filter->objectIdEqual = $USER_ID;
$pager = new KalturaFilterPager();
$pager->pageSize = 1;
$pager->pageIndex = 1;
$results = $client->metadata->listAction($filter, $pager)->objects;
if(count($results) == 0)
	echo 0;
else {
	$result = $results[0];
	$xml = simplexml_load_string($result->xml);
	$response = array();
	$response[0] = '<div class="purchaseTitles">Videos: </div>';
	$response[1] = '<div class="purchaseTitles">Channels: </div>';
	$entries = $xml->PurchasedEntries;
	$count = 0;
	for($j = count($entries) - 1; $j >= 0 && $count < 3; --$j) {
		if($entries[$j] != "") {
			$value = $client->media->get($entries[$j]);
			$categoryNames = explode(',', $value->categories);
			$title = $value->name."\n"."Belongs to channel(s): ";
			foreach($categoryNames as $categoryName)
				$title .= $categoryName.', ';
			$title = substr($title, 0, -2);
			$display =  '<img width="92" height="56" src="'.$value->thumbnailUrl.'" title="'.$title.'" >';
			$cats = $value->categoriesIds;
			$thumbnail = '<a class="thumblink" rel="'.$value->id.'" cats="'.$value->categoriesIds.'" style="margin-right: 8px;">'.$display.'</a>';
			$response[0] .= $thumbnail;
			++$count;
		}
	}
	$categories = $xml->PurchasedCategories;
	$count = 0;
	for($j = count($categories) - 1; $j >= 0 && $count < 3; --$j) {
		if($categories[$j] != "") {
			$value = $client->category->get($categories[$j]);
			$filter = new KalturaMediaEntryFilter();
			$filter->orderBy = "-createdAt";
			$filter->categoriesIdsMatchAnd = $value->id;
			$pager = new KalturaFilterPager();
			$pager->pageSize = 6;
			$pager->pageIndex = 1;
			$entries = $client->media->listAction($filter, $pager)->objects;
			$amount = 0;
			$link = "";
			$link .= '<div id="'.$value->id.'" class="categories" style="margin-right: 8px" title="'.$value->name.'">';
			$link .= '<div class="category">';
			foreach($entries as $categoryEntry) {
				$entry = $client->media->get($categoryEntry->id);
				$name = $entry->name;
				$display =  '<img width="30" height="17" src="'.$entry->thumbnailUrl.'">';
				$link .= $display;
				++$amount;
				if($amount == 3)
					$link .= '<div class="clearCategory"></div>';
			}
			$link .= '</div>';
			$link .= '<div class="categoryName">'.$value->name.'</div>';
			$link .= '</div>';
			$categoryLink = '<a class="categoryLink" rel="'.$value->id.'">'.$link.'</a>';
			$response[1] .= $categoryLink;
			++$count;
		}
	}
	if(strcmp($response[0], '<div class="purchaseTitles">Videos: </div>') == 0)
		$response[0] = '';
	if(strcmp($response[1], '<div class="purchaseTitles">Channels: </div>') == 0)
		$response[1] = '';
	$response[1] .= '<div class="clear"></div>';
	echo json_encode($response);
}