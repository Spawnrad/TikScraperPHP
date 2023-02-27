<?php
namespace TikScraper\Items;

use TikScraper\Cache;
use TikScraper\Helpers\Misc;
use TikScraper\Models\Feed;
use TikScraper\Models\Info;
use TikScraper\Sender;
use TikScraper\Models\Response;

class Hashtag extends Base {
    function __construct(string $term, Sender $sender, Cache $cache) {
        parent::__construct($term, 'hashtag', $sender, $cache);
    }

    public function info() {
        $req = $this->sender->sendHTML('/tag/' . $this->term, 'www', [
            'lang' => 'en'
        ]);
        $response = new Info;
        $response->setMeta($req);
        if ($response->meta->success) {
            $jsonData = Misc::extractSigi($req->data);
            if (isset($jsonData->MobileChallengePage)) {
                $this->sigi = $jsonData;
                $response->setDetail($jsonData->MobileChallengePage->challengeInfo->challenge);
                $response->setStats($jsonData->MobileChallengePage->challengeInfo->stats);
            }
        }
        $this->info = $response;
        return $this;
    }

    public function infoFromApi() {
        $query = [
            "challengeName" => $this->term,
        ];
        $req = $this->sender->sendApi('/api/challenge/detail', 'm', $query);
        $response = new Info;
        $response->setMeta($req);
        if ($response->meta->success) {
            if (isset($req->data) && isset ($req->data->challengeInfo)) {
                $response->setDetail($req->data->challengeInfo->challenge);
                $response->setStats($req->data->challengeInfo->stats);
            }
        }
        $this->info = $response;
        return $this;
    }

    public function setChallengeId($id) {
        $response = new Info;
        $req = new Response(true, 200, (object) ['id' => $id, 'statusCode' => 0, 'status_code' => 0]);
        $response->setMeta($req);
        $response->setDetail((object) $req->data);
        $this->info = $response;
        return $this;
    }

    public function feed(int $cursor = 0): self {
        $this->cursor = $cursor;

        if ($this->infoOk()) {
            $preloaded = $this->handleFeedPreload('challenge');
            if (!$preloaded) {
                $query = [
                    "count" => 30,
                    "challengeID" => $this->info->detail->id,
                    "cursor" => $cursor
                ];
                $req = $this->sender->sendApi('/api/challenge/item_list', 'm', $query);
                $response = new Feed;
                $response->fromReq($req, $cursor);
                $this->feed = $response;
            }
        }
        return $this;
    }
}
