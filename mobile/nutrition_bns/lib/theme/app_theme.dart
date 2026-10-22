import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

/// Valencia Nutrition field app — forest green, soft sage surfaces.
class AppColors {
  static const forest = Color(0xFF1B5E20);
  static const leaf = Color(0xFF2E7D32);
  static const mist = Color(0xFFE8F5E9);
  static const fog = Color(0xFFF4F7F4);
  static const ink = Color(0xFF1A2E1C);
  static const muted = Color(0xFF5C6B5E);
}

ThemeData buildNutritionTheme() {
  final scheme = ColorScheme.fromSeed(
    seedColor: AppColors.forest,
    brightness: Brightness.light,
    primary: AppColors.forest,
    secondary: AppColors.leaf,
    surface: Colors.white,
  ).copyWith(
    surfaceContainerLowest: AppColors.fog,
    surfaceContainerLow: AppColors.mist,
    onSurface: AppColors.ink,
    onSurfaceVariant: AppColors.muted,
  );

  final base = ThemeData(
    colorScheme: scheme,
    useMaterial3: true,
    scaffoldBackgroundColor: AppColors.fog,
    visualDensity: VisualDensity.standard,
  );

  return base.copyWith(
    appBarTheme: const AppBarTheme(
      elevation: 0,
      scrolledUnderElevation: 0.5,
      backgroundColor: AppColors.fog,
      foregroundColor: AppColors.ink,
      surfaceTintColor: Colors.transparent,
      systemOverlayStyle: SystemUiOverlayStyle.dark,
      titleTextStyle: TextStyle(
        color: AppColors.ink,
        fontSize: 17,
        fontWeight: FontWeight.w600,
        letterSpacing: -0.2,
      ),
    ),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: Colors.white,
      elevation: 0,
      height: 68,
      indicatorColor: AppColors.mist,
      labelTextStyle: WidgetStateProperty.resolveWith((states) {
        final selected = states.contains(WidgetState.selected);
        return TextStyle(
          fontSize: 12,
          fontWeight: selected ? FontWeight.w600 : FontWeight.w500,
          color: selected ? AppColors.forest : AppColors.muted,
        );
      }),
      iconTheme: WidgetStateProperty.resolveWith((states) {
        final selected = states.contains(WidgetState.selected);
        return IconThemeData(
          size: 22,
          color: selected ? AppColors.forest : AppColors.muted,
        );
      }),
    ),
    cardTheme: CardThemeData(
      color: Colors.white,
      elevation: 0,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: AppColors.forest.withValues(alpha: 0.08)),
      ),
    ),
    chipTheme: base.chipTheme.copyWith(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      side: BorderSide.none,
      padding: const EdgeInsets.symmetric(horizontal: 4),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        minimumSize: const Size.fromHeight(48),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        textStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        minimumSize: const Size.fromHeight(48),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        side: BorderSide(color: AppColors.forest.withValues(alpha: 0.35)),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      isDense: true,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide(color: AppColors.forest.withValues(alpha: 0.18)),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide(color: AppColors.forest.withValues(alpha: 0.18)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: AppColors.forest, width: 1.5),
      ),
      labelStyle: const TextStyle(color: AppColors.muted),
    ),
    snackBarTheme: SnackBarThemeData(
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
    ),
    dividerTheme: DividerThemeData(
      color: AppColors.forest.withValues(alpha: 0.08),
      space: 1,
    ),
    listTileTheme: const ListTileThemeData(
      contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 2),
    ),
  );
}
