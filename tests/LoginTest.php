<?php

class LoginTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testLogin()
    {
        $response = $this->call('GET', '/api/v1/login?email=surganda@gmail.com&password=123456');
        
        $this->assertEquals(200, $response->status());
    }
    
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testGetChecklist()
    {
        $response = $this->call('GET', '/api/v1/login?email=surganda@gmail.com&password=123456');
        $content  = json_decode($response->getContent());
        
        $headers = array(
            'Content-Type'  => 'application/json',
            'Authorization' => $content->api_key,
        );
        $server  = $this->transformHeadersToServerVars($headers);
        
        $response = $this->call('GET', '/api/v1/checklists', [], [], [], $server);
        $this->assertEquals(200, $response->status());
        
        $response = $this->call('PATCH', '/api/v1/checklists', [], [], [], $server);
        $this->assertEquals(405, $response->status());
    }
}
