<?php

use Mockery as m;

class MailerTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testMailerSendSendsMessageWithProperViewContent()
	{
		unset($_SERVER['__mailer.test']);
		$mailer = $this->getMock('Illuminate\Mail\Mailer', array('createMessage'), $this->getMocks());
		$message = m::mock('StdClass');
		$mailer->expects($this->once())->method('createMessage')->will($this->returnValue($message));
		$view = m::mock('StdClass');
		$mailer->getViewEnvironment()->shouldReceive('make')->once()->with('foo', array('data', 'message' => $message))->andReturn($view);
		$view->shouldReceive('render')->once()->andReturn('rendered.view');
		$message->shouldReceive('setBody')->once()->with('rendered.view', 'text/html');
		$message->shouldReceive('setFrom')->never();
		$mailer->setSwiftMailer(m::mock('StdClass'));
		$mailer->getSwiftMailer()->shouldReceive('send')->once()->with($message);
		$mailer->send('foo', array('data'), function($m) { $_SERVER['__mailer.test'] = $m; });
		unset($_SERVER['__mailer.test']);
	}


	public function testGlobalFromIsRespectedOnAllMessages()
	{
		unset($_SERVER['__mailer.test']);
		$mailer = $this->getMailer();
		$view = m::mock('StdClass');
		$mailer->getViewEnvironment()->shouldReceive('make')->once()->andReturn($view);
		$view->shouldReceive('render')->once()->andReturn('rendered.view');
		$mailer->setSwiftMailer(m::mock('StdClass'));
		$mailer->alwaysFrom('taylorotwell@gmail.com', 'Taylor Otwell');
		$me = $this;
		$mailer->getSwiftMailer()->shouldReceive('send')->once()->with(m::type('Illuminate\Mail\Message'))->andReturnUsing(function($message) use ($me)
		{
			$me->assertEquals(array('taylorotwell@gmail.com' => 'Taylor Otwell'), $message->getFrom());
		});
		$mailer->send('foo', array('data'), function($m) {});
	}


	protected function getMailer()
	{
		return new Illuminate\Mail\Mailer(m::mock('Illuminate\View\Environment'), m::mock('Swift_Mailer'));
	}


	protected function getMocks()
	{
		return array(m::mock('Illuminate\View\Environment'), m::mock('Swift_Mailer'));
	}

}