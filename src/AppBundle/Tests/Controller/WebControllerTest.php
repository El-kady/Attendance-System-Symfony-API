<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class WebControllerTest extends WebTestCase
{
    public function testQrcode()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/qrcode');
    }

}
