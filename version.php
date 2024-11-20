<?php
// This file is part of Stateful
//
// Stateful is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Stateful is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Stateful.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();
$plugin->version   = 2024112000;
$plugin->requires  = 2022041900;
$plugin->component = 'qbehaviour_stateful';
$plugin->maturity  = MATURITY_BETA;
$plugin->release   = '1.0.1 for Moodle 4.0+';
$plugin->dependencies = array(
    'qbehaviour_adaptivemultipart'     => 2018080600,
);