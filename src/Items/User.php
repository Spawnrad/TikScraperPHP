<?php
namespace TikScraper\Items;

use TikScraper\Cache;
use TikScraper\Constants\StaticUrls;
use TikScraper\Helpers\Misc;
use TikScraper\Models\Feed;
use TikScraper\Models\Info;
use TikScraper\Sender;

class User extends Base {
    private object $jsonData;

    function __construct(string $term, Sender $sender, Cache $cache) {
        parent::__construct($term, 'user', $sender, $cache);
        if (!isset($this->info)) {
            $this->info();
        }
    }

    public function info() {
        $req = $this->sender->sendHTML("/@{$this->term}", 'www', [
            'lang' => 'en'
        ]);
        $response = new Info;
        $response->setMeta($req);
        if ($response->meta->success) {
            $this->jsonData = Misc::extractSigi($req->data);
            if (isset($this->jsonData->UserModule)) {
                $response->setDetail($this->jsonData->UserModule->users->{$this->term});
                $response->setStats($this->jsonData->UserModule->stats->{$this->term});
            }
        }
        $this->info = $response;
    }

    public function feed(int $cursor = 0): self {
        $this->cursor = $cursor;
        $cached = $this->handleFeedCache();
        if (!$cached && $this->infoOk()) {
            $query = [
                "count" => 30,
                "id" => $this->info->detail->id,
                "cursor" => $cursor,
                "type" => 1,
                "secUid" => $this->info->detail->secUid,
                "sourceType" => 8,
                "appId" => 1233
            ];

            $req = $this->sender->sendApi('/api/post/item_list', 'm', $query, true, '', StaticUrls::USER_FEED);
            $response = new Feed;
            $response->fromReq($req, $cursor);
            $this->feed = $response;
        }
        return $this;
    }

    public function lastFeed(): self {
        $cached = $this->handleFeedCache();
        if (!$cached && $this->infoOk()) {
            $response = new Feed;
            $response->fromSigi($this->jsonData, $this->term);
            $this->feed = $response;
        }
        return $this;
    }
}
