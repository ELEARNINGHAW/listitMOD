<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    mod_listit
 * @copyright  2021 werner.welte@haw-hamburg.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... listit instance ID - it should be named as the first character of the module.

if ($id) {
    $cm         = get_coursemodule_from_id('listit', $id, 0, false, MUST_EXIST);
    $course     = $DB -> get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $listit     = $DB -> get_record('listit', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $listit     = $DB -> get_record('listit', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB -> get_record('course', array('id' => $listit->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('listit', $listit->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$event = \mod_listit\event\course_module_viewed::create(array(
    'objectid' => $PAGE -> cm -> instance,
    'context'  => $PAGE -> context,
));
$event->add_record_snapshot('course', $PAGE -> course);
$event->add_record_snapshot($PAGE -> cm -> modname, $listit);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/listit/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($listit -> name));
$PAGE->set_heading(format_string($course -> fullname));

/*
 * Other things you may want to set - remove if not needed.
 * $PAGE->set_cacheable(false);
 * $PAGE->set_focuscontrol('some-html-id');
 * $PAGE->add_body_class('listit-'.$somevar);
 */

// Output starts here.
echo $OUTPUT->header();

$coursecontext = context_course::instance($COURSE->id);

$USER -> role = 2;  /* Studi / Gast */

if ( has_capability( 'moodle/course:update', $coursecontext, $USER->id) ) 
{ 
  $USER -> role = 3; /* Dozent / Tutor */
}

$salt = '"dfgb%TBGI$"ert3QZU/ZU!335 "3 35ertwetwert%wertQer$tZHetr%&et$UetwertU"ertEe%tTert/e&rtEwe h$OrzPtr/zKertz(rtJzWertt%EUI §$O "$ $TZqerz wgr f"';

$current_user = array();
$current_user[ 'userVorname'   ] = ( $USER -> firstname   );
$current_user[ 'userNachname'  ] = ( $USER -> lastname    );
$current_user[ 'userKennung'   ] = ( $USER -> username    );
$current_user[ 'userID'        ] = ( $USER -> id 	      );
$current_user[ 'userEmail'     ] = ( $USER -> email       );
$current_user[ 'userRole'      ] = ( $USER -> role        );
$current_user[ 'courseID'      ] = ( $COURSE -> id        );

$current_user[ 'courseName'    ] = ( $COURSE -> fullname  );
$current_user[ 'token'         ] =  hash('sha512',
$current_user[ 'userVorname'   ] .
$current_user[ 'userNachname'  ] .
$current_user[ 'userKennung'   ] .
$current_user[ 'userID'        ] .
$current_user[ 'userEmail'     ] .
$current_user[ 'courseID'      ] .
$current_user[ 'courseName'    ] .
$salt );

if ( isset( $_SERVER[ 'SERVER_NAME' ] ) AND ( $_SERVER[ 'SERVER_NAME' ] )   == 'localhost' )
     { $URL = "http://localhost/haw/";                                      } # Dev-Server
else { $URL = "https://lernserver.el.haw-hamburg.de/haw/";                  } # Live-Server

$srvpath = $URL."listitApp/index.php"  ; 

$srvpath .=
   "?ufn=" .rawurlencode( base64_encode( $current_user[ 'userVorname'   ]  ) )
  ."&uln=" .rawurlencode( base64_encode( $current_user[ 'userNachname'  ]  ) )
  ."&uid=" .rawurlencode( base64_encode( $current_user[ 'userKennung'   ]  ) )
  ."&uun=" .rawurlencode( base64_encode( $current_user[ 'userID'        ]  ) )
  ."&uem=" .rawurlencode( base64_encode( $current_user[ 'userEmail'     ]  ) )
  ."&cid=" .rawurlencode( base64_encode( $current_user[ 'courseID'      ]  ) )
  ."&cfn=" .rawurlencode( base64_encode( $current_user[ 'courseName'    ]  ) )
  ."&tok=" .rawurlencode( base64_encode( $current_user[ 'token'         ]  ) )
  ."&rol=" .rawurlencode( base64_encode( $current_user[ 'userRole'      ]  ) )
  ."&rnd=" .rand(100000, 999999);


$content = "<iframe allowfullscreen allowfullscreen = \"true\"  border=\"0\" frameborder=\"0\" src=\"" .$srvpath. "\" style=\"width:100% ; height:1000px ;  display: block;\" ></iframe>";

echo $OUTPUT->box($content, "generalbox center clearfix");

echo $OUTPUT->footer();