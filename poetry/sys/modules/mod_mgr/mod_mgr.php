<?php

Class mod_mgr Extends Module {

	public function mgrTitle($link){
		return theme::a( _T('System Mgr Title'), $link);
	}

	public function ac_show(){

		$userObj	= prog::get('user');
		$poetryObj	= prog::get('poetry');
		$folderObj	= prog::get('folder');
		$playlistObj= prog::get('playlist');
		$tagObj		= prog::get('mod_tag');

		$this->user		= $userObj->getCnt();
		$this->poetry	= $poetryObj->getCnt();
		$this->folder	= $folderObj->getCnt();
		$this->playlist	= $playlistObj->getCnt();
		$this->tag		= $tagObj->getCnt();
	}

	public function vw_show(){

		echo theme::block(_T('System Mgr Title'), '
			<div class="row">

				<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
					<a href="' . WEB_ROOT . '/mgr/userMgr">

						<div class="dashboard blue clearfix">
							<div class="title"> ' . _T('Member Status') . '</div>

							<div class="visual">
								' . theme::icon('user') . '
							</div>

							<div class="details">
								<div class="number">'
									. number_format($this->user['total']) . ' / '
									. number_format($this->user['notAuth']) . ' / '
									. number_format($this->user['verified'])
								. '</div>

								<div class="desc">
									' . _T('Total Member') . ' / ' .  _T('None Auth Member') . ' / ' . _T('Verified Member') . '
								</div>
							</div>
						</div>
					</a>
				</div>

				<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
					<a href="' . WEB_ROOT . '/mgr/poetryMgr">
						<div class="dashboard green clearfix">
							<div class="title"> ' . _T('Poetry Status') . '</div>

							<div class="visual">
								' . theme::icon('music') . '
							</div>

							<div class="details">
								<div class="number">'
									. number_format($this->poetry['total']) .
								'</div>

								<div class="desc">
									' . _T('Total Poetry') . '
								</div>
							</div>
						</div>
					</a>
				</div>

				<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
					<a href="' . WEB_ROOT . '/mgr/folderMgr">
						<div class="dashboard red clearfix">
							<div class="title"> ' . _T('Folder Status') . '</div>

							<div class="visual">
								' . theme::icon('folder') . '
							</div>

							<div class="details">
								<div class="number">'
									. number_format($this->folder['total']) .
								'</div>

								<div class="desc">
									' . _T('Total Folder') . '
								</div>
							</div>
						</div>
					</a>
				</div>

				<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
					<div class="dashboard purple clearfix">
						<div class="title"> ' . _T('Playlist Status') . '</div>

						<div class="visual">
							' . theme::icon('list-ol') . '
						</div>

						<div class="details">
							<div class="number">'
								. number_format($this->playlist['total']) .
							'</div>

							<div class="desc">
								' . _T('Total Playlist') . '
							</div>
						</div>
					</div>
				</div>

				<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
					<div class="dashboard yellow clearfix">
						<div class="title"> ' . _T('Tag Status') . '</div>

						<div class="visual">
							' . theme::icon('tags') . '
						</div>

						<div class="details">
							<div class="number">'
								. number_format($this->tag) .
							'</div>

							<div class="desc">
								' . _T('Total Tag') . '
							</div>
						</div>
					</div>
				</div>
			</div>');
	}

}