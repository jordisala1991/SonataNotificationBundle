<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\NotificationBundle\Tests\Notification;

use Sonata\NotificationBundle\Backend\RuntimeBackend;
use Sonata\NotificationBundle\Exception\HandlingException;
use Sonata\NotificationBundle\Model\MessageInterface;
use Sonata\NotificationBundle\Tests\Entity\Message;
use Sonata\NotificationBundle\Tests\Helpers\PHPUnit_Framework_TestCase;

class RuntimeBackendTest extends PHPUnit_Framework_TestCase
{
    public function testCreateAndPublish()
    {
        $dispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $backend = new RuntimeBackend($dispatcher);
        $message = $backend->createAndPublish('foo', array('message' => 'salut'));

        $this->assertInstanceOf('Sonata\\NotificationBundle\\Model\\MessageInterface', $message);

        $this->assertEquals(MessageInterface::STATE_DONE, $message->getState());
        $this->assertNotNull($message->getCreatedAt());
        $this->assertEquals('foo', $message->getType());
        $this->assertEquals(array('message' => 'salut'), $message->getBody());
    }

    public function testIterator()
    {
        $dispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $backend = new RuntimeBackend($dispatcher);

        $this->assertInstanceOf('Iterator', $backend->getIterator());
    }

    public function testHandleSuccess()
    {
        $message = new Message();

        $dispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $dispatcher->expects($this->once())->method('dispatch');

        $backend = new RuntimeBackend($dispatcher);

        $backend->handle($message, $dispatcher);
        $this->assertEquals(MessageInterface::STATE_DONE, $message->getState());
        $this->assertNotNull($message->getCreatedAt());
        $this->assertNotNull($message->getCompletedAt());
    }

    public function testHandleError()
    {
        $message = new Message();

        $dispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $dispatcher->expects($this->once())->method('dispatch')->will($this->throwException(new \RuntimeException()));

        $backend = new RuntimeBackend($dispatcher);

        $e = false;

        try {
            $backend->handle($message, $dispatcher);
        } catch (HandlingException $e) {
        }

        $this->assertInstanceOf('Sonata\NotificationBundle\Exception\HandlingException', $e);

        $this->assertEquals(MessageInterface::STATE_ERROR, $message->getState());
        $this->assertNotNull($message->getCreatedAt());
        $this->assertNotNull($message->getCompletedAt());
    }
}
