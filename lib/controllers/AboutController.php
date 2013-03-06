<?php
class AboutController 
{
	private $deps;

	public function __construct(DependencyHelper $deps) 
	{		
		$this->deps = $deps;		
	}

	public function processRequest($args='') 
	{		
		if (is_array($args)) {
			switch ($args[0]) {
				case 'Us':
					$this->Us();
					exit();
				case 'Features-and-Benefits':
					$this->FeaturesAndBenefits();
					exit();
				case 'Signing-Up-and-Getting-Started':
					$this->SigningUpAndGettingStarted();
					exit();
				case 'Terms-of-Service':
					$this->TermsOfService();
					exit();
				case 'Privacy-Policy':
					$this->PrivacyPolicy();
					exit();
				case 'Seeking-Beta':
					$this->SeekingBeta();
					exit;
				case 'Learn-More':
					$this->LearnMore();
					exit;
			}
		}

		// If $args is not an array, this is a bad request		
		$this->badPage();
		exit();
	}

	protected function Us()
	{
		$request = $this->deps['request'];
		$view = $this->deps['view'];

		echo $view->render('about.us.html', array('output_type'=>$request->output_type));
	}

	protected function FeaturesAndBenefits()
	{
		$request = $this->deps['request'];
		$view = $this->deps['view'];

		echo $view->render('features.and.benefits.html', array('output_type'=>$request->output_type));
	}

	protected function SigningUpAndGettingStarted()
	{
		$request = $this->deps['request'];
		$view = $this->deps['view'];

		echo $view->render('signing.up.and.getting.started.html', array('output_type'=>$request->output_type));
	}

	protected function TermsOfService()
	{
		$request = $this->deps['request'];
		$view = $this->deps['view'];

		echo $view->render('terms.of.service.html', array('output_type'=>$request->output_type));
	}

	protected function PrivacyPolicy()
	{
		$request = $this->deps['request'];
		$view = $this->deps['view'];

		echo $view->render('privacy.policy.html', array('output_type'=>$request->output_type));
	}

	protected function LearnMore()
	{
		$request = $this->deps['request'];
		$view = $this->deps['view'];
		
		echo $view->render('learn.more.html', array('output_type'=>$request->output_type));
	}

	protected function SeekingBeta()
	{
		$request = $this->deps['request'];
		$view = $this->deps['view'];
		
		if ($request->output_type == 'partial') {
			echo $view->render('seeking.beta.html', array('output_type'=>$request->output_type));
		} else {
			echo $view->render('full.seeking.beta.html');
		}
	}

	protected function badPage() 
	{
		$request = $this->deps['request'];
		$view = $this->deps['view'];

		echo $view->render('404.html', array('output_type'=>$request->output_type));
	}
}
