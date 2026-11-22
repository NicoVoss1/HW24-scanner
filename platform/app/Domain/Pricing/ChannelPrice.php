<?php
declare(strict_types=1);
namespace App\Domain\Pricing;
use InvalidArgumentException;

/** Immutable, source-specific price observation. Never silently infer gross from net. */
final readonly class ChannelPrice
{
    private const CHANNELS = ['jtl','shopify','amazon','ebay'];
    private const TYPES = ['standard','customer_group','own_offer','market_offer','buy_box','lowest_offer'];
    private const STATUSES = ['verified','derived','unverified','unavailable'];

    public function __construct(
        public string $channel,
        public string $type,
        public string $currency,
        public ?string $gross,
        public ?string $net,
        public string $status,
        public ?string $source,
        public ?string $observedAt,
        public ?string $marketplace = null,
        public ?string $account = null,
        public ?string $taxCountry = null,
        public ?string $customerGroup = null,
        public ?string $offerId = null,
        public ?string $publicationStatus = null,
    ) {
        if (!in_array($channel, self::CHANNELS, true) ||
            !in_array($type, self::TYPES, true) ||
            !in_array($status, self::STATUSES, true) ||
            !preg_match('/^[A-Z]{3}$/D', $currency)) {
            throw new InvalidArgumentException('Invalid channel, price kind, status, or currency');
        }
        foreach (['gross' => $gross, 'net' => $net] as $key => $value) {
            if ($value !== null &&
                (!preg_match('/^(?:0|[1-9][0-9]{0,9})(?:\.[0-9]{1,4})?$/D', $value))) {
                throw new InvalidArgumentException('Invalid decimal price: '.$key);
            }
        }
        if ($status === 'verified' && (!$source || !$observedAt || ($gross === null && $net === null))) {
            throw new InvalidArgumentException('Verified price requires value, provenance and observation time');
        }
        if ($status === 'unavailable' && ($gross !== null || $net !== null)) {
            throw new InvalidArgumentException('Unavailable price must not claim a value');
        }
    }

    public function toArray(): array
    {
        return [
            'channel'=>$this->channel, 'price_type'=>$this->type,
            'currency'=>$this->currency, 'gross'=>$this->gross, 'net'=>$this->net,
            'status'=>$this->status, 'source'=>$this->source,
            'observed_at'=>$this->observedAt, 'marketplace'=>$this->marketplace,
            'account'=>$this->account, 'tax_country'=>$this->taxCountry,
            'customer_group'=>$this->customerGroup, 'offer_id'=>$this->offerId,
            'publication_status'=>$this->publicationStatus,
        ];
    }
}
