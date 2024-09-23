<?php defined('SYSPATH') or die('Kohana bootstrap needs to be included before tests run');

/**
 * Unit tests for response class
 *
 * @group          kohana
 * @group          kohana.core
 * @group          kohana.core.response
 *
 * @package        Kohana
 * @category       Tests
 * @author         Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license        http://kohanaframework.org/license
 */
class Kohana_ResponseTest extends Unittest_TestCase
{
	/**
	 * Provider for test_body
	 *
	 * @return array
	 */
	public function provider_body()
	{
		$view = $this->createMock('View');
		$view->expects($this->any())
			->method('__toString')
			->will($this->returnValue('foo'));

		return [
			['unit test', 'unit test'],
			[$view, 'foo'],
		];
	}

	/**
	 * Tests that we can set and read a body of a response
	 *
	 * @test
	 * @dataProvider provider_body
	 *
	 * @return null
	 */
	public function test_body($source, $expected)
	{
		$response = new Response;
		$response->body($source);
		$this->assertSame($response->body(), $expected);

		$response = (string) $response;
		$this->assertSame($response, $expected);
	}

	/**
	 * Provides data for test_body_string_zero()
	 *
	 * @return array
	 */
	public function provider_body_string_zero()
	{
		return [
			['0', '0'],
			["0", '0'],
			[0, '0'],
		];
	}

	/**
	 * Test that Response::body() handles numerics correctly
	 *
	 * @test
	 * @dataProvider provider_body_string_zero
	 *
	 * @param string $string
	 * @param string $expected
	 *
	 * @return void
	 */
	public function test_body_string_zero($string, $expected)
	{
		$response = new Response;
		$response->body($string);

		$this->assertSame($expected, $response->body());
	}

	public static function provider_set_json(): array
	{
		return [
			'simple body with defaults' => [
				['body' => ['some', 'thing']],
				[
					'body' => '["some","thing"]',
					'status' => 200,
					'headers' => ['content-type' => 'application/json'],
				],
			],
			'custom status code' => [
				['body' => ['some', 'thing'], 'status' => 409],
				[
					'body' => '["some","thing"]',
					'status' => 409,
					'headers' => ['content-type' => 'application/json'],
				],
			],
			'json serializable' => [
				[
					'body' => new class implements JsonSerializable {
						public function jsonSerialize(): array
						{
							return ['a' => 'value'];
						}
					},
				],
				['body' => '{"a":"value"}', 'status' => 200, 'headers' => ['content-type' => 'application/json']],
			],
			'unescaped slashes' => [
				['body' => 'some/thing'],
				['body' => '"some/thing"', 'status' => 200, 'headers' => ['content-type' => 'application/json']],
			],
		];
	}

	/**
	 * @dataProvider provider_set_json
	 */
	public function test_set_json(array $args, array $expected)
	{
		$response = new Response();
		$response->setJSON(...$args);
		$this->assertSame(
			$expected,
			['body' => $response->body(), 'status' => $response->status(), 'headers' => $response->headers()->getArrayCopy()],
		);
	}

	public function test_set_json_throws_on_json_error()
	{
		$response = new Response();
		$this->expectException(JsonException::class);
		$this->expectExceptionMessage('UTF-8');
		$response->setJSON(['bad' => "utf8 \xD0"]);
	}

	/**
	 * provider for test_cookie_set()
	 *
	 * @return array
	 */
	public function provider_cookie_set()
	{
		return [
			[
				'test1',
				'foo',
				[
					'test1' => [
						'value'      => 'foo',
						// Cookies default to the Cookie::$expiration which is set to 900 in our test
						'expiration' => 900,
					],
				],
			],
			[
				[
					'test2' => 'stfu',
					'test3' => [
						'value'      => 'snafu',
						'expiration' => 123456789,
					],
				],
				NULL,
				[
					'test2' => [
						'value'      => 'stfu',
						// Cookies default to the Cookie::$expiration which is set to 900 in our test
						'expiration' => 900,
					],
					'test3' => [
						'value'      => 'snafu',
						'expiration' => 123456789,
					],
				],
			],
		];
	}

	/**
	 * Tests the Response::cookie() method, ensures
	 * correct values are set, including defaults
	 *
	 * @test
	 * @dataProvider provider_cookie_set
	 *
	 * @param string $key
	 * @param string $value
	 * @param array  $expected
	 *
	 * @return void
	 */
	public function test_cookie_set($key, $value, $expected)
	{
		Cookie::$expiration = 900;
		// Setup the Response and apply cookie
		$response = new Response;
		$response->cookie($key, $value);

		foreach ($expected as $_key => $_value) {
			$cookie = $response->cookie($_key);

			$this->assertSame($_value['value'], $cookie['value']);
			$this->assertSame($_value['expiration'], $cookie['expiration']);
		}
	}

	/**
	 * Tests the Response::cookie() get functionality
	 *
	 * @return void
	 */
	public function test_cookie_get()
	{
		$response = new Response;

		// Test for empty cookies
		$this->assertSame([], $response->cookie());

		// Test for no specific cookie
		$this->assertNull($response->cookie('foobar'));

		$response->cookie('foo', 'bar');
		$cookie = $response->cookie('foo');

		$this->assertSame('bar', $cookie['value']);
		$this->assertSame(Cookie::$expiration, $cookie['expiration']);
	}

	/**
	 * Test the content type is sent when set
	 *
	 * @test
	 */
	public function test_content_type_when_set()
	{
		$content_type = 'application/json';
		$response     = new Response;
		$response->headers('content-type', $content_type);
		$headers = $response->send_headers()->headers();
		$this->assertSame($content_type, (string) $headers['content-type']);
	}
}
