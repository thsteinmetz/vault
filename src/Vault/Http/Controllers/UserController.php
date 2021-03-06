<?php namespace Rappasoft\Vault\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Config;

use Rappasoft\Vault\Repositories\User\UserRepositoryContract;
use Rappasoft\Vault\Repositories\Role\RoleRepositoryContract;

use Rappasoft\Vault\Exceptions\EntityNotValidException;
use Rappasoft\Vault\Exceptions\UserNeedsRolesException;

use Illuminate\Routing\Controller;

/**
 * Class UserController
 */
class UserController extends Controller {

	/**
	 * @var UserRepositoryContract
	 */
	protected $users;
	/**
	 * @var RoleRepositoryContract
	 */
	protected $roles;

	public function __construct(UserRepositoryContract $users, RoleRepositoryContract $roles) {
		$this->middleware('auth');

		$this->users = $users;
		$this->roles = $roles;
	}

	/**
	 * @return mixed
	 */
	public function index() {
		return view('vault::index')
			->withUsers($this->users->getUsersPaginated(Config::get('vault.users.default_per_page'), 1));
	}

	/**
	 * @return mixed
	 */
	public function create() {
		return view('vault::create')
			->withRoles($this->roles->getAllRoles('id', 'asc', true));
	}

	/**
	 * @return mixed
	 */
	public function store() {
		try {
			$this->users->create(Input::except('assignees_roles'), Input::only('assignees_roles'));
		} catch(EntityNotValidException $e) {
			return Redirect::back()->withInput()->withFlashDanger($e->validationErrors());
		} catch(UserNeedsRolesException $e) {
			return Redirect::route('access.users.edit', $e->userID())->withInput()->withFlashDanger($e->validationErrors());
		} catch(Exception $e) {
			return Redirect::back()->withInput()->withFlashDanger($e->getMessage());
		}

		return Redirect::route('access.users.index')->withFlashSuccess('The user was successfully created.');
	}

	/**
	 * @param $id
	 * @return mixed
	 */
	public function edit($id) {
		$user = $this->users->findOrThrowException($id, true);
		return view('vault::edit')
			->withUser($user)
			->withUserRoles($user->roles->lists('id'))
			->withRoles($this->roles->getAllRoles('id', 'asc', true));
	}

	/**
	 * @param $id
	 * @return mixed
	 */
	public function update($id) {
		try {
			$this->users->update($id, Input::except('assignees_roles'), Input::only('assignees_roles'));
		} catch(EntityNotValidException $e) {
			return Redirect::back()->withInput()->withFlashDanger($e->validationErrors());
		} catch(Exception $e) {
			return Redirect::back()->withInput()->withFlashDanger($e->getMessage());
		}

		return Redirect::route('access.users.index')->withFlashSuccess('The user was successfully updated.');
	}

	/**
	 * @param $id
	 * @return mixed
	 */
	public function destroy($id) {
		try {
			$this->users->destroy($id);
		} catch(Exception $e) {
			return Redirect::back()->withInput()->withFlashDanger($e->getMessage());
		}

		return Redirect::route('access.users.index')->withFlashSuccess('The user was successfully deleted.');
	}

	/**
	 * @param $id
	 * @return mixed
	 */
	public function delete($id) {
		try {
			$this->users->delete($id);
		} catch(Exception $e) {
			return Redirect::back()->withInput()->withFlashDanger($e->getMessage());
		}

		return Redirect::route('access.users.index')->withFlashSuccess('The user was deleted permanently.');
	}

	/**
	 * @param $id
	 * @return mixed
	 */
	public function restore($id) {
		try {
			$this->users->restore($id);
		} catch(Exception $e) {
			return Redirect::back()->withInput()->withFlashDanger($e->getMessage());
		}

		return Redirect::route('access.users.index')->withFlashSuccess('The user was successfully restored.');
	}

	/**
	 * @param $id
	 * @param $status
	 * @return mixed
	 */
	public function mark($id, $status) {
		try {
			$this->users->mark($id, $status);
		} catch(Exception $e) {
			return Redirect::back()->withInput()->withFlashDanger($e->getMessage());
		}

		return Redirect::route('access.users.index')->withFlashSuccess('The user was successfully updated.');
	}

	/**
	 * @return mixed
	 */
	public function deactivated() {
		return view('vault::deactivated')
			->withUsers($this->users->getUsersPaginated(25, 0));
	}

	/**
	 * @return mixed
	 */
	public function deleted() {
		return view('vault::deleted')
			->withUsers($this->users->getDeletedUsersPaginated(25));
	}

	/**
	 * @param $id
	 * @return mixed
	 */
	public function changePassword($id) {
		return view('vault::change-password')
			->withUser($this->users->findOrThrowException($id));
	}

	/**
	 * @param $id
	 * @return mixed
	 */
	public function updatePassword($id) {
		try {
			$this->users->updatePassword($id, Input::all());
		} catch(EntityNotValidException $e) {
			return Redirect::back()->withInput()->withFlashDanger($e->validationErrors());
		} catch(Exception $e) {
			return Redirect::back()->withInput()->withFlashDanger($e->getMessage());
		}

		return Redirect::route('access.users.index')->withFlashSuccess("The user's password was successfully updated.");
	}

}