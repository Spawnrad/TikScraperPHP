<?php
namespace TikScraper\Items;

use TikScraper\Cache;
use TikScraper\Helpers\Misc;
use TikScraper\Models\Feed;
use TikScraper\Models\Info;
use TikScraper\Models\Response;
use TikScraper\Sender;

class Video extends Base {
    private ?object $item = null;

    function __construct(string $term, Sender $sender, Cache $cache) {
        parent::__construct($term, 'video', $sender, $cache);
        if (!isset($this->info)) {
            $this->info();
        }
    }

    public function info() {
        $subdomain = '';
        $endpoint = '';
        if (is_numeric($this->term)) {
            $subdomain = 'm';
            $endpoint = '/v/' . $this->term;
        } else {
            $subdomain = 'www';
            $endpoint = '/t/' . $this->term;
        }

        $req = $this->sender->sendHTML($endpoint, $subdomain);
        $response = new Info;
        $response->setMeta($req);
        if ($response->meta->success) {
            $jsonData = Misc::extractSigi($req->data);
            if (isset($jsonData->ItemModule, $jsonData->ItemList, $jsonData->UserModule)) {
                $this->term = $jsonData->ItemList->video->keyword;
                $this->item = $jsonData->ItemModule->{$this->term};
                $this->item->author = $jsonData->UserModule->users->{$this->item->author};
                $response->setDetail($this->item->author);
                $response->setStats($this->item->stats);
            }
        }
        $this->info = $response;
    }

    public function feed(): self {
        $this->cursor = 0;
        $cached = $this->handleFeedCache();
        if (!$cached && $this->infoOk()) {
            $response = new Feed;
            $response->setItems([$this->item]);
            $response->setNav(false, null, '');
            $response->setMeta(new Response(true, 200, "PLACEHOLDER"));
            $this->feed = $response;
        }
        return $this;
    }

    public function comments(int $cursor = 0) {
        $this->cursor = $cursor;
        $query = [
            'aweme_id' =>  $this->term,
            'count' =>  '30',
            'cursor'=> $this->cursor,
        ];
        $req = $this->sender->sendApi('/api/comment/list/', 'www', $query);

        $response = new Feed;
        $response->fromReq($req, $cursor);
        $this->feed = $response;
        return $this;
    }
}
