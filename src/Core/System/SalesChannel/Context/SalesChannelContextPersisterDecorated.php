<?php declare(strict_types=1);

namespace RecentlyViewedProduct\Core\System\SalesChannel\Context;

use Doctrine\DBAL\Connection;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Shopware\Core\Checkout\Cart\CartPersisterInterface;

class SalesChannelContextPersisterDecorated extends SalesChannelContextPersister
{
    /**
     * @var SalesChannelContextPersister
     */
    private $decorated;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(
        SalesChannelContextPersister $decorated,
        Connection $connection,
        EventDispatcherInterface $eventDispatcher,
        CartPersisterInterface $cartPersister
    ) {
        $this->decorated = $decorated;
        $this->connection = $connection;

        parent::__construct($connection, $eventDispatcher, $cartPersister);
    }

    public function replace(string $oldToken, SalesChannelContext $context): string
    {
        $newToken = $this->decorated->replace($oldToken, $context);

        $this->connection->executeUpdate(
            'UPDATE `recently_viewed_product`
                   SET `token` = :newToken
                   WHERE `token` = :oldToken',
            [
                'newToken' => $newToken,
                'oldToken' => $oldToken,
            ]
        );

        return $newToken;
    }

    public function delete(string $token, ?string $salesChannelId = null, ?string $customerId = null): void
    {
        $this->decorated->delete($token);

        $this->connection->executeUpdate(
            'DELETE FROM recently_viewed_product WHERE token = :token',
            [
                'token' => $token,
            ]
        );
    }
}
