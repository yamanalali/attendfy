import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class SingleMenu extends StatelessWidget {
  final IconData icon;
  final String menuName;
  final Color? color;
  final Function()? action;
  final String? decName;

  const SingleMenu({
    super.key,
    required this.icon,
    required this.menuName,
    this.color,
    this.action,
    this.decName,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: action,
      child: Padding(
        padding: const EdgeInsetsDirectional.fromSTEB(0, 10, 0, 10),
        child: Container(
          width: MediaQuery.of(context).size.width * 0.44,
          height: 244,
          decoration: BoxDecoration(
            color: Colors.white,
            boxShadow: const [
              BoxShadow(
                blurRadius: 4,
                color: Color(0x34090F13),
                offset: Offset(0, 2),
              ),
            ],
            borderRadius: BorderRadius.circular(16),
          ),
          child: Padding(
            padding: const EdgeInsetsDirectional.fromSTEB(12, 8, 12, 8),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 100.0,
                  height: 100.0,
                  decoration: BoxDecoration(
                    color: color,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    icon,
                    size: 40.0,
                    color: Colors.white,
                  ),
                ),
                Text(
                  menuName,
                  style: GoogleFonts.quicksand(
                    fontSize: 20,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                Text(
                  decName!,
                  textAlign: TextAlign.left,
                  style: GoogleFonts.quicksand(
                    fontSize: 12,
                    fontWeight: FontWeight.w200,
                    color: Colors.grey[700],
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
