import 'package:attendancewithfingerprint/screen/attendance_page.dart';
import 'package:attendancewithfingerprint/screen/login_page.dart';
import 'package:attendancewithfingerprint/screen/report_page.dart';
import 'package:attendancewithfingerprint/screen/setting_page.dart';
import 'package:attendancewithfingerprint/utils/single_menu.dart';
import 'package:attendancewithfingerprint/utils/strings.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:geolocator/geolocator.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'about_page.dart';

class MainMenuPage extends StatelessWidget {
  const MainMenuPage({super.key});

  @override
  Widget build(BuildContext context) {
    return const Menu();
  }
}

class Menu extends StatefulWidget {
  const Menu({super.key});

  @override
  MenuState createState() => MenuState();
}

class MenuState extends State<Menu> {
  String? getEmail = "";

  @override
  void initState() {
    _getPermission();
    getPref();
    super.initState();
  }

  Future<void> _getPermission() async {
    getPermissionAttendance();
  }

  Future<void> getPermissionAttendance() async {
    await [
      Permission.camera,
      Permission.location,
      Permission.locationWhenInUse,
    ].request().then((value) {
      _determinePosition();
    });
  }

  Future<Position> _determinePosition() async {
    bool serviceEnabled;
    LocationPermission permission;

    // Test if location services are enabled.
    serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      getSnackBar('Location services are disabled.');
    }

    permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        getSnackBar('Location permissions are denied');
      }
    }

    if (permission == LocationPermission.deniedForever) {
      getSnackBar(
        'Location permissions are permanently denied, we cannot request permissions.',
      );
    }

    return Geolocator.getCurrentPosition();
  }

  // Show snackBar
  ScaffoldFeatureController<SnackBar, SnackBarClosedReason> getSnackBar(
    String messageSnackBar,
  ) {
    return ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(messageSnackBar)));
  }

  // Function sign out
  Future<void> _signOut() async {
    final SharedPreferences preferences = await SharedPreferences.getInstance();
    setState(() {
      preferences.remove("status");
      preferences.remove("email");
      preferences.remove("password");
      preferences.remove("id");

      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (context) => const LoginPage()),
      );
    });
  }

  Future<void> getPref() async {
    final SharedPreferences preferences = await SharedPreferences.getInstance();
    setState(() {
      getEmail = preferences.getString("email");
    });
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Scaffold(
        backgroundColor: const Color(0xFFF1F4F8),
        body: SingleChildScrollView(
          child: Container(
            margin: const EdgeInsets.only(bottom: 40.0),
            child: Column(
              children: [
                Container(
                  width: double.infinity,
                  height: 150.0,
                  decoration: BoxDecoration(
                    color: const Color(0xFF0E67B4),
                    borderRadius: const BorderRadius.only(
                      bottomLeft: Radius.circular(100),
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.grey.withOpacity(0.6),
                        spreadRadius: 5,
                        blurRadius: 10,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Image(
                        image: AssetImage('images/logo.png'),
                      ),
                      const SizedBox(
                        width: 10.0,
                      ),
                      Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            "$mainMenuTitleHi ${getEmail!},",
                            style: GoogleFonts.quicksand(
                              fontSize: 13.0,
                              color: Colors.white,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(
                            height: 5.0,
                          ),
                          Text(
                            mainMenuTitleUserName,
                            style: GoogleFonts.quicksand(
                              fontSize: 18.0,
                              color: Colors.white,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                SingleChildScrollView(
                  child: Column(
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                        children: [
                          SingleMenu(
                            icon: FontAwesomeIcons.userClock,
                            menuName: mainMenuCheckIn,
                            color: Colors.blue,
                            action: () => Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (context) => const AttendancePage(
                                  query: 'in',
                                  title: mainMenuCheckInTitle,
                                ),
                              ),
                            ),
                            decName: mainMenuCheckInDec,
                          ),
                          SingleMenu(
                            icon: FontAwesomeIcons.solidClock,
                            menuName: mainMenuCheckOut,
                            color: Colors.teal,
                            action: () => Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (context) => const AttendancePage(
                                  query: 'out',
                                  title: mainMenuCheckOutTitle,
                                ),
                              ),
                            ),
                            decName: mainMenuCheckOutDec,
                          ),
                        ],
                      ),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                        children: [
                          SingleMenu(
                            icon: FontAwesomeIcons.gears,
                            menuName: mainMenuSettings,
                            color: Colors.green,
                            action: () => Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (context) => const SettingPage(),
                              ),
                            ),
                            decName: mainMenuSettingsDec,
                          ),
                          SingleMenu(
                            icon: FontAwesomeIcons.calendar,
                            menuName: mainMenuReport,
                            color: Colors.yellow[700],
                            action: () => Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (context) => const ReportPage(),
                              ),
                            ),
                            decName: mainMenuReportDec,
                          ),
                        ],
                      ),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                        children: [
                          SingleMenu(
                            icon: FontAwesomeIcons.userLarge,
                            menuName: mainMenuAbout,
                            color: Colors.purple,
                            action: () => Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (context) => const AboutPage(),
                              ),
                            ),
                            decName: mainMenuAboutDec,
                          ),
                          SingleMenu(
                            icon: FontAwesomeIcons.rightFromBracket,
                            menuName: mainMenuLogout,
                            color: Colors.red[300],
                            action: () => _signOut(),
                            decName: mainMenuLogoutDec,
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
