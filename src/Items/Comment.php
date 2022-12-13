<?php
namespace TikScraper\Items;

use TikScraper\Cache;
use TikScraper\Sender;
use TikScraper\Models\Feed;

class Comment extends Base {
    function __construct(string $term, Sender $sender, Cache $cache) {
        parent::__construct($term, 'comment', $sender, $cache);
        $this->feed();
    }

    public function feed(int $cursor = 0): self {
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
