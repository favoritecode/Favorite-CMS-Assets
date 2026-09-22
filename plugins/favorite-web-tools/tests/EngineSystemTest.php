<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Tests;

use FavoriteCMS\Tools\Engines\CssEngine;
use FavoriteCMS\Tools\Engines\EngineResolver;
use FavoriteCMS\Tools\Engines\HtmlEngine;
use FavoriteCMS\Tools\Engines\JavaScriptEngine;
use FavoriteCMS\Tools\Engines\PhpEngine;
use FavoriteCMS\Tools\Engines\PythonApiEngine;
use FavoriteCMS\Tools\Handlers\Base64DecoderHandler;
use FavoriteCMS\Tools\Handlers\Base64EncoderHandler;
use FavoriteCMS\Tools\Handlers\HashGeneratorHandler;
use FavoriteCMS\Tools\Handlers\JsonFormatterHandler;
use FavoriteCMS\Tools\Handlers\JsonValidatorHandler;
use FavoriteCMS\Tools\Handlers\PhpHandlerRegistry;
use FavoriteCMS\Tools\Handlers\TextCaseConverterHandler;
use FavoriteCMS\Tools\Handlers\TextCounterHandler;
use FavoriteCMS\Tools\Handlers\UrlDecoderHandler;
use FavoriteCMS\Tools\Handlers\UrlEncoderHandler;
use FavoriteCMS\Tools\Handlers\UuidGeneratorHandler;
use FavoriteCMS\Tools\Models\Tool;
use PHPUnit\Framework\TestCase;

class EngineSystemTest extends TestCase
{
    private EngineResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new EngineResolver();
    }

    public function testEngineResolverResolvesAllFiveEngines(): void
    {
        $this->assertInstanceOf(HtmlEngine::class, $this->resolver->resolve('HTML'));
        $this->assertInstanceOf(CssEngine::class, $this->resolver->resolve('CSS'));
        $this->assertInstanceOf(JavaScriptEngine::class, $this->resolver->resolve('JAVASCRIPT'));
        $this->assertInstanceOf(PhpEngine::class, $this->resolver->resolve('PHP'));
        $this->assertInstanceOf(PythonApiEngine::class, $this->resolver->resolve('PYTHON_API'));

        $this->expectException(\InvalidArgumentException::class);
        $this->resolver->resolve('RUBY_ENGINE');
    }

    public function testHtmlEngineOperations(): void
    {
        $engine = new HtmlEngine();

        // 1. Format
        $toolFormat = new Tool(['engine' => 'HTML', 'configuration' => ['operation' => 'format']]);
        $formatRes = $engine->execute($toolFormat, ['html' => '<div><p>Hello World</p></div>']);
        $this->assertTrue($formatRes['success']);
        $this->assertEquals('HTML', $formatRes['type']);
        $this->assertStringContainsString('<p>Hello World</p>', (string)$formatRes['data']);

        // 2. Minify
        $toolMinify = new Tool(['engine' => 'HTML', 'configuration' => ['operation' => 'minify']]);
        $minifyRes = $engine->execute($toolMinify, ['html' => "<div>\n   <p>   Hello   </p>\n</div>"]);
        $this->assertTrue($minifyRes['success']);
        $this->assertStringNotContainsString("\n", (string)$minifyRes['data']);

        // 3. Encode & Decode
        $toolEncode = new Tool(['engine' => 'HTML', 'configuration' => ['operation' => 'encode']]);
        $encodeRes = $engine->execute($toolEncode, ['html' => '<script>alert("xss")</script>']);
        $this->assertTrue($encodeRes['success']);
        $this->assertStringContainsString('&lt;script&gt;', (string)$encodeRes['data']);

        $toolDecode = new Tool(['engine' => 'HTML', 'configuration' => ['operation' => 'decode']]);
        $decodeRes = $engine->execute($toolDecode, ['html' => '&lt;b&gt;bold&lt;/b&gt;']);
        $this->assertTrue($decodeRes['success']);
        $this->assertEquals('<b>bold</b>', $decodeRes['data']);
    }

    public function testCssEngineOperations(): void
    {
        $engine = new CssEngine();

        // 1. Format
        $toolFormat = new Tool(['engine' => 'CSS', 'configuration' => ['operation' => 'format']]);
        $formatRes = $engine->execute($toolFormat, ['css' => 'body{color:red;margin:0;}']);
        $this->assertTrue($formatRes['success']);
        $this->assertEquals('CSS', $formatRes['type']);
        $this->assertStringContainsString("body {\n", (string)$formatRes['data']);

        // 2. Minify
        $toolMinify = new Tool(['engine' => 'CSS', 'configuration' => ['operation' => 'minify']]);
        $minifyRes = $engine->execute($toolMinify, ['css' => "body {\n    color: red;\n    margin: 0;\n}"]);
        $this->assertTrue($minifyRes['success']);
        $this->assertEquals('body{color:red;margin:0;}', trim((string)$minifyRes['data']));

        // 3. Color converter
        $toolColor = new Tool(['engine' => 'CSS', 'configuration' => ['operation' => 'color_convert']]);
        $colorRes = $engine->execute($toolColor, ['color' => '#ffffff']);
        $this->assertTrue($colorRes['success']);
        $this->assertIsArray($colorRes['data']);
        $this->assertEquals('rgb(255, 255, 255)', $colorRes['data']['rgb']);
    }

    public function testJavaScriptEngineOperations(): void
    {
        $engine = new JavaScriptEngine();

        // Minify
        $toolMinify = new Tool(['engine' => 'JAVASCRIPT', 'configuration' => ['operation' => 'minify']]);
        $js = "function hello() {\n    // comment\n    var x = 1;\n    return x;\n}";
        $minifyRes = $engine->execute($toolMinify, ['javascript' => $js]);
        $this->assertTrue($minifyRes['success']);
        $this->assertEquals('TEXT', $minifyRes['type']);
        $this->assertStringNotContainsString('// comment', (string)$minifyRes['data']);
    }

    public function testPhpControlledHandlers(): void
    {
        // 1. JSON Formatter
        $jsonHandler = new JsonFormatterHandler();
        $formatted = $jsonHandler->handle(['json' => '{"name":"John","age":30}']);
        $this->assertTrue($formatted['success']);
        $this->assertEquals('JSON', $formatted['type']);
        $this->assertStringContainsString('"name": "John"', (string)$formatted['data']);

        // JSON Validator
        $validHandler = new JsonValidatorHandler();
        $this->assertTrue($validHandler->handle(['json' => '{"valid":true}'])['data']['valid']);
        $this->assertFalse($validHandler->handle(['json' => '{invalid json}'])['data']['valid']);

        // 2. Base64 Encoder / Decoder
        $b64Enc = new Base64EncoderHandler();
        $encRes = $b64Enc->handle(['text' => 'Hello Favorite CMS!']);
        $this->assertTrue($encRes['success']);
        $this->assertEquals(base64_encode('Hello Favorite CMS!'), $encRes['data']);

        $b64Dec = new Base64DecoderHandler();
        $decRes = $b64Dec->handle(['text' => $encRes['data']]);
        $this->assertTrue($decRes['success']);
        $this->assertEquals('Hello Favorite CMS!', $decRes['data']);

        // 3. URL Encoder / Decoder
        $urlEnc = new UrlEncoderHandler();
        $urlDec = new UrlDecoderHandler();
        $uEnc = $urlEnc->handle(['text' => 'hello world&test=1']);
        $this->assertEquals('hello+world%26test%3D1', $uEnc['data']);
        $uDec = $urlDec->handle(['text' => $uEnc['data']]);
        $this->assertEquals('hello world&test=1', $uDec['data']);

        // 4. UUID Generator
        $uuidGen = new UuidGeneratorHandler();
        $uRes = $uuidGen->handle(['count' => 3]);
        $this->assertTrue($uRes['success']);
        $this->assertCount(3, $uRes['data']['uuids']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uRes['data']['uuids'][0]);

        // 5. Hash Generator
        $hashGen = new HashGeneratorHandler();
        $hRes = $hashGen->handle(['text' => 'password123', 'algorithm' => 'sha256']);
        $this->assertTrue($hRes['success']);
        $this->assertEquals(hash('sha256', 'password123'), $hRes['data']['hash']);

        // 6. Text Case Converter
        $caseGen = new TextCaseConverterHandler();
        $camelRes = $caseGen->handle(['text' => 'hello world', 'mode' => 'camel']);
        $this->assertEquals('helloWorld', $camelRes['data']);

        $snakeRes = $caseGen->handle(['text' => 'hello world', 'mode' => 'snake']);
        $this->assertEquals('hello_world', $snakeRes['data']);

        // 7. Text Counter
        $counter = new TextCounterHandler();
        $cntRes = $counter->handle(['text' => 'Quick brown fox. Jumps over lazy dog.']);
        $this->assertEquals(7, $cntRes['data']['words']);
        $this->assertEquals(2, $cntRes['data']['sentences']);
    }

    public function testSecurityPhpEngineRejectsArbitraryCode(): void
    {
        $phpEngine = new PhpEngine();

        // Attempt to pass arbitrary code or unwhitelisted class
        $maliciousTool = new Tool([
            'name'          => 'Malicious Tool',
            'slug'          => 'malicious-tool',
            'engine'        => 'PHP',
            'handler_class' => 'SystemExecHandler',
            'configuration' => ['handler_class' => 'SystemExecHandler'],
        ]);

        $res = $phpEngine->execute($maliciousTool, ['code' => 'system("whoami");']);
        $this->assertFalse($res['success']);
        $this->assertStringContainsString('not registered', $res['error']);

        // Even with eval input, registered handlers only process data
        $b64 = new Base64EncoderHandler();
        $safeRes = $b64->handle(['text' => 'phpinfo();']);
        $this->assertEquals(base64_encode('phpinfo();'), $safeRes['data']);
    }

    public function testExtendedPhpHandlers(): void
    {
        // 1. Password Generator
        $pwdHandler = \FavoriteCMS\Tools\Handlers\PhpHandlerRegistry::get('password_generator');
        $this->assertNotNull($pwdHandler);
        $pRes = $pwdHandler->handle(['length' => 18, 'symbols' => true, 'numbers' => true]);
        $this->assertTrue($pRes['success']);
        $this->assertEquals(18, strlen((string)$pRes['data']));
        $this->assertGreaterThan(0, $pRes['meta']['entropy_bits']);

        // 2. Markdown Previewer
        $mdHandler = \FavoriteCMS\Tools\Handlers\PhpHandlerRegistry::get('markdown_previewer');
        $this->assertNotNull($mdHandler);
        $mRes = $mdHandler->handle(['input' => "# Heading\n**bold text**\n[malicious](javascript:alert(1))"]);
        $this->assertTrue($mRes['success']);
        $this->assertStringContainsString('<h1>Heading</h1>', (string)$mRes['data']);
        $this->assertStringContainsString('<strong>bold text</strong>', (string)$mRes['data']);
        $this->assertStringNotContainsString('javascript:', (string)$mRes['data']); // XSS sanitized

        // 3. Number Base Converter
        $baseHandler = \FavoriteCMS\Tools\Handlers\PhpHandlerRegistry::get('number_base_converter');
        $this->assertNotNull($baseHandler);
        $bRes = $baseHandler->handle(['input' => '255', 'from_base' => 10, 'to_base' => 16]);
        $this->assertTrue($bRes['success']);
        $this->assertEquals('FF', $bRes['data']['hexadecimal']);
        $this->assertEquals('11111111', $bRes['data']['binary']);

        // 4. HTTP Status Lookup
        $httpHandler = \FavoriteCMS\Tools\Handlers\PhpHandlerRegistry::get('http_status_lookup');
        $this->assertNotNull($httpHandler);
        $hRes = $httpHandler->handle(['code' => 404]);
        $this->assertTrue($hRes['success']);
        $this->assertEquals('Not Found', $hRes['data']['message']);
        $this->assertEquals('4xx Client Error', $hRes['data']['category']);

        // 5. User Agent Parser
        $uaHandler = \FavoriteCMS\Tools\Handlers\PhpHandlerRegistry::get('user_agent_parser');
        $this->assertNotNull($uaHandler);
        $uaStr = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        $uaRes = $uaHandler->handle(['input' => $uaStr]);
        $this->assertTrue($uaRes['success']);
        $this->assertEquals('Google Chrome', $uaRes['data']['browser']);
        $this->assertEquals('Windows', $uaRes['data']['operating_system']);
        $this->assertEquals('Desktop', $uaRes['data']['device_type']);

        // 6. Query String Parser
        $qsHandler = \FavoriteCMS\Tools\Handlers\PhpHandlerRegistry::get('query_string_parser');
        $this->assertNotNull($qsHandler);
        $qsRes = $qsHandler->handle(['input' => 'https://example.com/search?q=favorite+cms&category=plugins&sort=desc']);
        $this->assertTrue($qsRes['success']);
        $this->assertEquals('favorite cms', $qsRes['data']['parameters']['q']);
        $this->assertEquals('plugins', $qsRes['data']['parameters']['category']);

        // 7. Slug Generator
        $slugHandler = \FavoriteCMS\Tools\Handlers\PhpHandlerRegistry::get('slug_generator');
        $this->assertNotNull($slugHandler);
        $sRes = $slugHandler->handle(['input' => 'Hello World & Favorite Web Tools!']);
        $this->assertTrue($sRes['success']);
        $this->assertEquals('hello-world-favorite-web-tools', $sRes['data']);

        // 8. PHP Serializer & Safe Unserializer
        $serHandler = \FavoriteCMS\Tools\Handlers\PhpHandlerRegistry::get('php_serializer');
        $this->assertNotNull($serHandler);
        $sampleData = ['framework' => 'Favorite CMS', 'version' => 2];
        $serRes = $serHandler->handle(['input' => json_encode($sampleData), 'action' => 'serialize']);
        $this->assertTrue($serRes['success']);
        $serializedStr = $serRes['data'];
        $this->assertStringStartsWith('a:2:', $serializedStr);

        $unserRes = $serHandler->handle(['input' => $serializedStr, 'action' => 'unserialize']);
        $this->assertTrue($unserRes['success']);
        $this->assertEquals('Favorite CMS', $unserRes['data']['framework']);

        // Security check: Unserializing serialized object with allowed_classes => false returns __PHP_Incomplete_Class
        $maliciousObjectStr = 'O:8:"stdClass":1:{s:4:"test";s:4:"data";}';
        $objRes = $serHandler->handle(['input' => $maliciousObjectStr, 'action' => 'unserialize']);
        $this->assertTrue($objRes['success']);
        $this->assertInstanceOf(\__PHP_Incomplete_Class::class, $objRes['data']);

        // 9. PHP Array to JSON (Tokenizer AST parser, no eval)
        $arrToJson = \FavoriteCMS\Tools\Handlers\PhpHandlerRegistry::get('php_array_to_json');
        $this->assertNotNull($arrToJson);
        $phpCode = "['name' => 'Favorite CMS', 'active' => true, 'count' => 42, 'sub' => ['a', 'b']]";
        $jRes = $arrToJson->handle(['input' => $phpCode]);
        $this->assertTrue($jRes['success']);
        $this->assertEquals('Favorite CMS', $jRes['data']['name']);
        $this->assertTrue($jRes['data']['active']);
        $this->assertEquals(42, $jRes['data']['count']);
        $this->assertEquals(['a', 'b'], $jRes['data']['sub']);

        // Negative check: Disallowed code / function call in array must be rejected
        $evilCode = "['evil' => system('whoami')]";
        $evilRes = $arrToJson->handle(['input' => $evilCode]);
        $this->assertFalse($evilRes['success']);
        $this->assertStringContainsString('Disallowed', $evilRes['error']);
    }
}

