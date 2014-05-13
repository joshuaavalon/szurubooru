<?php
class ScorePostJobTest extends AbstractTest
{
	public function testScoring()
	{
		$this->loginRandomPerson();
		$post = $this->postMocker->mockSingle();
		$this->assert->areEqual(0, $post->getScore());

		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new ScorePostJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_POST_SCORE => 1,
				]);
		});

		$this->assert->areEqual(1, $post->getScore());
	}

	public function testNegativeScore()
	{
		$this->loginRandomPerson();
		$post = $this->postMocker->mockSingle();
		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new ScorePostJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_POST_SCORE => -1,
				]);
		});

		$this->assert->areEqual(-1, $post->getScore());
	}

	public function testInvalidScore()
	{
		$this->loginRandomPerson();
		$post = $this->postMocker->mockSingle();
		$this->assert->throws(function() use ($post)
		{
			Api::run(
				new ScorePostJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_POST_SCORE => 2,
				]);
		}, 'Invalid score');

		$this->assert->areEqual(0, $post->getScore());
	}

	public function testScoreOverwriting()
	{
		$this->loginRandomPerson();
		$post = $this->postMocker->mockSingle();
		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new ScorePostJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_POST_SCORE => -1,
				]);
		});

		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new ScorePostJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_POST_SCORE => 1,
				]);
		});

		$this->assert->areEqual(1, $post->getScore());
	}

	public function testScoreTwoPeople()
	{
		$this->loginRandomPerson();
		$post = $this->postMocker->mockSingle();
		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new ScorePostJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_POST_SCORE => 1,
				]);
		});

		$this->loginRandomPerson();
		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new ScorePostJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_POST_SCORE => 1,
				]);
		});

		$this->assert->areEqual(2, $post->getScore());
	}

	private function loginRandomPerson()
	{
		$this->grantAccess('scorePost');
		$this->login($this->userMocker->mockSingle());
	}
}
