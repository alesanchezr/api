<?php
namespace Tests;

use PHPUnit\Framework\TestCase;

class BadgeTest extends BaseTestCase {
 
    protected $app;
    public function setUp()
    {
        parent::setUp();
        $this->app->addRoutes(['badge']);
        $this->app->addRoutes(['specialty']);

    }

    function testForCreateBadge(){
        $body = [
            "slug" => "identator",
            "name" => "Identatior for xxxxxxx",
            "points_to_achieve" => 100,
            "technologies" => "css, html",
            "description" => "wululu"
        ];
        $badge = $this->mockAPICall(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/badge/'], $body)
                ->expectSuccess()
                ->withPropertiesAndValues($body)
                ->getParsedBody();
        
        return $badge->data;
    }

    function testForCreateBadge2(){
        $body = [
            "slug" => "identatorr",
            "name" => "Identatior for xxxxxxx",
            "points_to_achieve" => 100,
            "technologies" => "css, html",
            "description" => "wululu"
        ];
        $badge = $this->mockAPICall(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/badge/'], $body)
                ->expectSuccess()
                ->withPropertiesAndValues($body)
                ->getParsedBody();
        
        return $badge->data;
    }

    // ------- Specialty -------

    function testForCreateSpecialty(){
        $body = [
            "name" => "RTF Master",
            "slug" => "rtf-master",
            "image_url" => "",
            "description" => "Loren ipsum orbat thinkin ir latbongen sidoment",
            "badges" => ["identator","identatorr"],
            "points_to_achieve" => 40,
            "description" => "Create websites using a CMS"
        ];
        $profile = $this->mockAPICall(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/specialty/'], $body)
            ->expectSuccess()
            ->getParsedBody();

        return $profile->data;
    }

    function testCreateSpecialtyDescriptionEmpty(){
        $body = [
            "name" => "RTFF",
            "slug" => "rtf-masterr",
            "image_url" => "",
            "description" => "",
            "badges" => ["identator","identatorr"],
            "points_to_achieve" => 40,
            "description" => "Create websites using a CMS"
        ];
        $profile = $this->mockAPICall(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/specialty/'], $body)
            ->expectSuccess()
            ->getParsedBody();

        return $profile->data;
    }

    function testGetIsNotEmptyStudent(){
        $this->mockAPICall(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/badges/student/1'])
                ->expectSuccess();
    }

    /**
     * @depends testForCreateBadge
     */
    function testGetSendParametersNumbersBadge($badge){
        $responseObj = $this->mockAPICall(['REQUEST_METHOD' => 'GET','REQUEST_URI' => '/badge/'.$badge->id])
            ->expectSuccess()
            ->getParsedBody();
    }

    /**
     * @depends testForCreateBadge
     */
    function testGetForNameBadge($badge){
        $responseObj = $this->mockAPICall(['REQUEST_METHOD' => 'GET','REQUEST_URI' => '/badge/'.$badge->id])
            ->expectSuccess();
    }

    /**
     * @depends testForCreateBadge
     */
    function testGetSendParametersCharacterSpecialBadge($badge){
        $responseObj = $this->mockAPICall(['REQUEST_METHOD' => 'GET','REQUEST_URI' => '/badge/'.$badge->description])
            ->expectFailure()
            ->getParsedBody();
    }

    function testCreateDoubleSlug(){
        $body = [
            "slug" => "identator",
            "name" => "Identatior for xxxxxxx",
            "points_to_achieve" => 100,
            "technologies" => "css, html",
            "description" => "wululu"
        ];
        $badge = $this->mockAPICall(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/badge/'], $body)
                ->expectFailure();
    }

    function testSlugCharacterSpecials(){
        $body = [
            "slug" => "12%ˆˆ&",
            "name" => "Identatior for xxxxxxx",
            "points_to_achieve" => 100,
            "technologies" => "css, html",
            "description" => "wululu"
        ];
        $badge = $this->mockAPICall(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/badge/'], $body)
            ->expectFailure()
            ->getParsedBody();
    }

    function testEmptySlug(){
        $body = [
            "slug" => "",
            "name" => "Identatior for xxxxxxx",
            "points_to_achieve" => 100,
            "technologies" => "css, html",
            "description" => "wululu"
        ];
        $badge = $this->mockAPICall(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/badge/'], $body)
            ->expectFailure()
            ->getParsedBody();
    }

    function testEmpty(){
        $body = [
            "slug" => "prueba",
            "name" => "",
            "points_to_achieve" => '',
            "technologies" => "",
            "description" => ""
        ];
        $badge = $this->mockAPICall(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/badge/'], $body)
            ->expectSuccess()
            ->getParsedBody();
    }

    function testPointLetters(){
        $body = [
            "slug" => "prueba2",
            "name" => "Identatior for xxxxxxx",
            "points_to_achieve" => 'hola',
            "technologies" => "css, html",
            "description" => "wululu"
        ];
        $badge = $this->mockAPICall(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/badge/'], $body)
            ->expectSuccess()
            ->getParsedBody();
    }

    function testTechnologiesNumbers(){
        $body = [
            "slug" => "prueba3",
            "name" => "Identatior for xxxxxxx",
            "points_to_achieve" => 100,
            "technologies" => 123,
            "description" => "wululu"
        ];
        $badge = $this->mockAPICall(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/badge/'], $body)
            ->expectSuccess()
            ->getParsedBody();
    }

    function testDescriptionTechnologiesCharacterSpecials(){
        $body = [
            "slug" => "prueba4",
            "name" => "Identatior for xxxxxxx",
            "points_to_achieve" => 100,
            "technologies" => 123,
            "description" => "wululu!@#!$"
        ];
        $badge = $this->mockAPICall(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/badge/'], $body)
            ->expectSuccess()
            ->getParsedBody();
    }

    function testCreateDoubleDescription(){
        $body = [
            "slug" => "prueba5",
            "name" => "Identatior for xxxxxxx",
            "points_to_achieve" => 100,
            "technologies" => 123,
            "description" => "wululu"
        ];
        $badge = $this->mockAPICall(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/badge/'], $body)
            ->expectSuccess()
            ->getParsedBody();
    }

    function testGetAllBadge(){
        $this->mockAPICall(['REQUEST_METHOD' => 'GET','REQUEST_URI' => '/badges/'])
            ->expectSuccess();
    }

    function testGetIsNotEmpty(){
        $responseObj = $this->mockAPICall(['REQUEST_METHOD' => 'GET','REQUEST_URI' => '/badges/'])
            ->expectSuccess()
            ->getParsedBody();
        $this->assertNotEmpty($responseObj->data);
        //assertNotEmpty afirma que no esta vacio
    }

    /**
     * @depends testForCreateBadge
     */
    function testUpdateSendParametersNumbers($badge){
        $body = [
            "slug" => "identator-update",
            "name" => "Identatior for xxxxxxx",
            "points_to_achieve" => 100,
            "technologies" => "css, html",
            "description" => "wululu"
        ];
        $responseObj = $this->mockAPICall(['REQUEST_METHOD' => 'POST','REQUEST_URI' => '/badge/'.$badge->id], $body)
            ->expectSuccess();
    }

    /**
     * @depends testForCreateBadge
     */
    function testUpdateSendParametersCharacterSpecial($badge){
        $body = [
            "slug" => "identator-update",
            "name" => "Identatior for xxxxxxx",
            "points_to_achieve" => 100,
            "technologies" => "css, html",
            "description" => "wululu"
        ];
        $responseObj = $this->mockAPICall(['REQUEST_METHOD' => 'POST','REQUEST_URI' => '/badge/'.$badge->description], $body)
            ->expectFailure();
    }

    /**
     * @depends testForCreateBadge
     */
    function testUpdateBadgeForName($badge){
        $body = [
            "slug" => "identator-update2",
            "name" => "Identatior for Rafael",
            "points_to_achieve" => 100,
            "technologies" => "css, html",
            "description" => "wululu"
        ];
        $responseObj = $this->mockAPICall(['REQUEST_METHOD' => 'POST','REQUEST_URI' => '/badge/'.$badge->id], $body)
            ->expectSuccess()
            ->withPropertiesAndValues($body);
    }

    /**
     * @depends testForCreateBadge
     */
    /*function testBadgeToSpecialty($badge){
        $body = [
            "badges" => [1, 2]
        ];
        $responseObj = $this->mockAPICall(['REQUEST_METHOD' => 'POST','REQUEST_URI' => '/badge/specialty/'.$specialty->id], $body)
            ->expectSuccess()
            ->withPropertiesAndValues($body);
    }

    /**
     * @depends testForCreateBadge
     */
    function testDeleteBadge($badge){
        $responseObj = $this->mockAPICall(['REQUEST_METHOD' => 'delete','REQUEST_URI' => '/badge/'.$badge->id])
            ->expectSuccess()
            ->getParsedBody();
        
        return $responseObj->data;
    }

    /**
     * @depends testForCreateBadge
     */
    function testDeletedBadge($badge){
        $responseObj = $this->mockAPICall(['REQUEST_METHOD' => 'delete','REQUEST_URI' => '/badge/'.$badge->id])
            ->expectFailure()
            ->getParsedBody();
    }
}