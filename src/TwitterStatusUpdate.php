<?php

namespace NotificationChannels\Twitter;

use Illuminate\Support\Collection;
use Kylewm\Brevity\Brevity;
use NotificationChannels\Twitter\Exceptions\CouldNotSendNotification;

class TwitterStatusUpdate extends TwitterMessage
{
    public ?Collection $imageIds = null;
    private ?array $images = null;
    private ?array $taggedUserIds = null;

    /**
     * @throws CouldNotSendNotification
     */
    public function __construct(string $content)
    {
        parent::__construct($content);

        if ($exceededLength = $this->messageIsTooLong(new Brevity())) {
            throw CouldNotSendNotification::statusUpdateTooLong($exceededLength);
        }
    }

    public function getApiEndpoint(): string
    {
        return 'tweets';
    }

    /**
     * Set Twitter media files.
     *
     * @return $this
     */
    public function withImage(array|string $images): static
    {
        $images = is_array($images) ? $images : [$images];

        collect($images)->each(function ($image) {
            $this->images[] = new TwitterImage($image);
        });

        return $this;
    }

    /**
     * Tag a user in the attached media with the user id.
     *
     * @return $this
     */
    public function withTaggedUserId(array|string $taggedUserId): static
    {
        collect(is_array($taggedUserId) ? $taggedUserId : [$taggedUserId])->each(function ($taggedUserId) {
            $this->taggedUserIds[] = $taggedUserId;
        });

        return $this;
    }

    /**
     * Get Twitter images list.
     */
    public function getImages(): ?array
    {
        return $this->images;
    }

    /**
     * Build Twitter request body.
     */
    public function getRequestBody(): array
    {
        $body = ['text' => $this->getContent()];

        if ($this->imageIds instanceof Collection) {
            $body['media'] = [
                'media_ids' => $this->imageIds,
                'tagged_user_ids' => $this->taggedUserIds
            ];
        }

        return $body;
    }

    /**
     * Check if the message length is too long.
     *
     * @return int How many characters the max length is exceeded or 0 when it isn't.
     */
    private function messageIsTooLong(Brevity $brevity): int
    {
        $tweetLength = $brevity->tweetLength($this->content);
        $exceededLength = $tweetLength - 280;

        return max($exceededLength, 0);
    }
}
