var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.UserAccountSettingsPresenter = function(
	_,
	jQuery,
	util,
	promise,
	api,
	auth,
	messagePresenter) {

	var target;
	var templates = {};
	var user;
	var privileges;
	var avatarContent;
	var fileDropper;

	function init(args, loaded) {
		user = args.user;
		target = args.target;

		privileges = {
			canBan:
				auth.hasPrivilege(auth.privileges.ban),
			canChangeAccessRank:
				auth.hasPrivilege(auth.privileges.changeAccessRank),
			canChangeAvatarStyle:
				auth.hasPrivilege(auth.privileges.changeAllAvatarStyles) ||
				(auth.hasPrivilege(auth.privileges.changeOwnAvatarStyle) && auth.isLoggedIn(user.name)),
			canChangeName:
				auth.hasPrivilege(auth.privileges.changeAllNames) ||
				(auth.hasPrivilege(auth.privileges.changeOwnName) && auth.isLoggedIn(user.name)),
			canChangeEmailAddress:
				auth.hasPrivilege(auth.privileges.changeAllEmailAddresses) ||
				(auth.hasPrivilege(auth.privileges.changeOwnEmailAddress) && auth.isLoggedIn(user.name)),
			canChangePassword:
				auth.hasPrivilege(auth.privileges.changeAllPasswords) ||
				(auth.hasPrivilege(auth.privileges.changeOwnPassword) && auth.isLoggedIn(user.name)),
		};

		promise.wait(util.promiseTemplate('account-settings'))
			.then(function(template) {
				templates.accountRemoval = template;
				render();
				loaded();
			});
	}

	function render() {
		var $el = jQuery(target);
		$el.html(templates.accountRemoval(_.extend({user: user}, privileges)));
		$el.find('form').submit(accountSettingsFormSubmitted);
		$el.find('form [name=avatar-style]').change(avatarStyleChanged);
		avatarStyleChanged();
		fileDropper = new App.Controls.FileDropper($el.find('[name=avatar-content]'));
		fileDropper.onChange = avatarContentChanged;
		fileDropper.setNames = true;
	}

	function getPrivileges() {
		return privileges;
	}

	function avatarStyleChanged(e) {
		var $el = jQuery(target);
		var $target = $el.find('.avatar-content .file-handler');
		if ($el.find('[name=avatar-style]:checked').val() === 'manual') {
			$target.show();
		} else {
			$target.hide();
		}
	}

	function avatarContentChanged(files) {
		if (files.length === 1) {
			fileDropper.readAsDataURL(files[0], function(content) {
				avatarContent = content;
			});
		}
	}

	function accountSettingsFormSubmitted(e) {
		e.preventDefault();
		var $el = jQuery(target);
		var $messages = jQuery(target).find('.messages');
		messagePresenter.hideMessages($messages);
		var formData = {};

		if (privileges.canChangeAvatarStyle) {
			formData.avatarStyle = $el.find('[name=avatar-style]:checked').val();
			if (avatarContent) {
				formData.avatarContent = avatarContent;
			}
		}
		if (privileges.canChangeName) {
			formData.userName = $el.find('[name=userName]').val();
		}
		if (privileges.canChangeEmailAddress) {
			formData.email = $el.find('[name=email]').val();
		}
		if (privileges.canChangePassword) {
			formData.password = $el.find('[name=password]').val();
			formData.passwordConfirmation = $el.find('[name=passwordConfirmation]').val();
		}
		if (privileges.canChangeAccessRank) {
			formData.accessRank = $el.find('[name=access-rank]:checked').val();
		}
		if (privileges.canBan) {
			formData.banned = $el.find('[name=ban]').is(':checked') ? 1 : 0;
		}

		if (!validateAccountSettingsFormData(formData)) {
			return;
		}

		if (!formData.password) {
			delete formData.password;
			delete formData.passwordConfirmation;
		}

		promise.wait(api.put('/users/' + user.name, formData))
			.then(function(response) {
				editSuccess(response);
			}).fail(function(response) {
				editFailure(response);
			});
	}

	function editSuccess(apiResponse) {
		var wasLoggedIn = auth.isLoggedIn(user.name);
		user = apiResponse.json;
		if (wasLoggedIn) {
			auth.updateCurrentUser(user);
		}

		render();

		var $messages = jQuery(target).find('.messages');
		var message = 'Account settings updated!';
		if (!apiResponse.json.confirmed) {
			message += '<br/>Check your inbox for activation e-mail.<br/>If e-mail doesn\'t show up, check your spam folder.';
		}
		messagePresenter.showInfo($messages, message);
	}

	function editFailure(apiResponse) {
		var $messages = jQuery(target).find('.messages');
		messagePresenter.showError($messages, apiResponse.json && apiResponse.json.error || apiResponse);
	}

	function validateAccountSettingsFormData(formData) {
		var $messages = jQuery(target).find('.messages');
		if (formData.password !== formData.passwordConfirmation) {
			messagePresenter.showError($messages, 'Passwords must be the same.');
			return false;
		}

		return true;
	}

	return {
		init: init,
		render: render,
		getPrivileges: getPrivileges,
	};

};

App.DI.register('userAccountSettingsPresenter', ['_', 'jQuery', 'util', 'promise', 'api', 'auth', 'messagePresenter'], App.Presenters.UserAccountSettingsPresenter);
