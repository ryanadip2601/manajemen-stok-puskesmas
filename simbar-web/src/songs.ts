// Ten public-domain melodies (classical pieces and traditional folk songs), arranged for acoustic guitar.
// Melody: "NOTE:beats" tokens ("r" is a rest). Chords: [symbol, beats]; "-" leaves the guitar part silent.
// Style is the right-hand pattern: arp8 (4/4 eighths), arp6 (3/4 eighths), triplet, gymno (bass + chord), bass3.

export type Song = {
  id: string; title: string; note: string; bpm: number; style: 'arp8' | 'arp6' | 'triplet' | 'gymno' | 'bass3';
  shift?: number; repeat?: number; melody: string; chords: [string, number][];
};

const bars = (list: string, beats: number) => list.split(' ').map((symbol) => [symbol, beats] as [string, number]);
const loop = <T,>(items: T[], times: number) => Array.from({ length: times }, () => items).flat();

export const SONGS: Song[] = [
  {
    id: 'romance', title: 'Romance (Spanyol)', note: 'Petikan gitar klasik, anonim', bpm: 76, style: 'triplet',
    melody: 'B4:1 B4:1 B4:1 B4:1 A4:1 G4:1 G4:1 F#4:1 E4:1 E4:1 G4:1 B4:1 E5:1 E5:1 E5:1 E5:1 D5:1 C5:1 C5:1 B4:1 A4:1 A4:1 B4:1 C5:1 '
      + 'B4:1 C5:1 B4:1 D#5:1 C5:1 B4:1 B4:1 A4:1 G4:1 G4:1 F#4:1 E4:1 F#4:1 F#4:1 F#4:1 F#4:1 G4:1 F#4:1 E4:1 E4:1 E4:1 E4:3',
    chords: bars('Em Em Em Em Em Em Am Am B7 B7 Em Em B7 B7 Em Em', 3),
  },
  {
    id: 'canon', title: 'Canon in D', note: 'Johann Pachelbel', bpm: 66, style: 'arp8',
    melody: 'F#5:2 E5:2 D5:2 C#5:2 B4:2 A4:2 B4:2 C#5:2 D5:2 C#5:2 B4:2 A4:2 G4:2 F#4:2 G4:2 E4:2 '
      + 'D4:1 F#4:1 A4:1 G4:1 F#4:1 D4:1 F#4:1 E4:1 D4:1 B3:1 D4:1 A4:1 G4:1 B4:1 A4:1 G4:1 '
      + 'F#5:2 E5:2 D5:2 C#5:2 B4:2 A4:2 B4:2 C#5:2 D5:4',
    chords: [...loop(bars('D A Bm F#m G D G A', 2), 4).slice(0, 32), ['D', 4]],
  },
  {
    id: 'greensleeves', title: 'Greensleeves', note: 'Balada Inggris abad ke-16', bpm: 96, style: 'arp6',
    melody: 'r:2 A4:1 C5:2 D5:1 E5:1.5 F5:.5 E5:1 D5:2 B4:1 G4:1.5 A4:.5 B4:1 C5:2 A4:1 A4:1.5 G#4:.5 A4:1 B4:2 G#4:1 E4:2 A4:1 '
      + 'C5:2 D5:1 E5:1.5 F5:.5 E5:1 D5:2 B4:1 G4:1.5 A4:.5 B4:1 C5:1.5 B4:.5 A4:1 G#4:1.5 F#4:.5 G#4:1 A4:3 A4:3 '
      + 'G5:3 G5:1.5 F#5:.5 E5:1 D5:2 B4:1 G4:1.5 A4:.5 B4:1 C5:2 A4:1 A4:1.5 G#4:.5 A4:1 B4:2 G#4:1 E4:3 '
      + 'G5:3 G5:1.5 F#5:.5 E5:1 D5:2 B4:1 G4:1.5 A4:.5 B4:1 C5:1.5 B4:.5 A4:1 G#4:1.5 F#4:.5 G#4:1 A4:3 A4:3',
    chords: [['-', 2], ['Am', 1], ...bars('Am Am G G Am Am E E Am Am G G Am E Am Am C C G G Am Am E E C C G G Am E Am Am', 3)],
  },
  {
    id: 'elise', title: 'Für Elise', note: 'Ludwig van Beethoven', bpm: 132, style: 'bass3', repeat: 4,
    melody: 'E5:.5 D#5:.5 E5:.5 D#5:.5 E5:.5 B4:.5 D5:.5 C5:.5 A4:1 r:.5 C4:.5 E4:.5 A4:.5 B4:1 r:.5 E4:.5 G#4:.5 B4:.5 '
      + 'C5:1 r:.5 E4:.5 E5:.5 D#5:.5 E5:.5 D#5:.5 E5:.5 B4:.5 D5:.5 C5:.5 A4:1 r:.5 C4:.5 E4:.5 A4:.5 B4:1 r:.5 E4:.5 C5:.5 B4:.5 A4:3',
    chords: [['-', 1], ['-', 3], ...bars('Am E Am', 3), ['-', 3], ...bars('Am E Am', 3)],
  },
  {
    id: 'gymnopedie', title: 'Gymnopédie No. 1', note: 'Erik Satie, sangat tenang', bpm: 72, style: 'gymno',
    melody: 'r:12 r:1 F#5:1 A5:1 G5:1 F#5:1 C#5:1 B4:1 C#5:1 D5:1 A4:3 F#4:3 F#4:3 F#4:3 F#4:3 '
      + 'r:1 F#5:1 A5:1 G5:1 F#5:1 C#5:1 B4:1 C#5:1 D5:1 A4:3 C#5:3 F#5:3 E5:3 E5:3',
    chords: loop(bars('Gmaj7 Dmaj7', 3), 10),
  },
  {
    id: 'scarborough', title: 'Scarborough Fair', note: 'Lagu rakyat Inggris', bpm: 90, style: 'arp6',
    melody: 'A4:2 A4:1 E5:2 E5:1 B4:1.5 C5:.5 B4:1 A4:3 r:1 E5:1 G5:1 A5:2 G5:1 E5:1 F#5:1 D5:1 E5:3 '
      + 'r:2 A5:1 A5:2 A5:1 G5:2 E5:1 E5:1 D5:1 C5:1 B4:2 G4:1 A4:2 E5:1 D5:2 C5:1 B4:1 A4:1 G4:1 A4:3 A4:3',
    chords: bars('Am Am G Am C Am D Em Am Am C Am G Am D G Am Am', 3),
  },
  {
    id: 'ode', title: 'Ode to Joy', note: 'Ludwig van Beethoven', bpm: 100, style: 'arp8', shift: 12,
    melody: 'F#4:1 F#4:1 G4:1 A4:1 A4:1 G4:1 F#4:1 E4:1 D4:1 D4:1 E4:1 F#4:1 F#4:1.5 E4:.5 E4:2 '
      + 'F#4:1 F#4:1 G4:1 A4:1 A4:1 G4:1 F#4:1 E4:1 D4:1 D4:1 E4:1 F#4:1 E4:1.5 D4:.5 D4:2 '
      + 'E4:1 E4:1 F#4:1 D4:1 E4:1 F#4:.5 G4:.5 F#4:1 D4:1 E4:1 F#4:.5 G4:.5 F#4:1 E4:1 D4:1 E4:1 A3:2 '
      + 'F#4:1 F#4:1 G4:1 A4:1 A4:1 G4:1 F#4:1 E4:1 D4:1 D4:1 E4:1 F#4:1 E4:1.5 D4:.5 D4:2',
    chords: [...bars('D A D', 4), ['D', 2], ['A', 2], ...bars('D A D', 4), ['A', 2], ['D', 2],
      ['A', 4], ['A', 2], ['D', 2], ['A', 2], ['D', 2], ['D', 2], ['A', 2], ...bars('D A D', 4), ['A', 2], ['D', 2]],
  },
  {
    id: 'brahms', title: 'Lullaby (Wiegenlied)', note: 'Johannes Brahms, nina bobo', bpm: 84, style: 'arp6', shift: 12,
    melody: 'r:2 E4:.5 E4:.5 G4:2 E4:.5 E4:.5 G4:2 E4:.5 G4:.5 C5:1 B4:1.5 A4:.5 A4:1 G4:1 D4:.5 E4:.5 F4:1 D4:1 D4:.5 E4:.5 '
      + 'F4:2 D4:.5 F4:.5 B4:.5 A4:.5 G4:1 B4:1 C5:2 C4:.5 C4:.5 C5:2 A4:.5 F4:.5 G4:2 E4:.5 C4:.5 F4:1 G4:1 A4:1 '
      + 'G4:2 C4:.5 C4:.5 C5:2 A4:.5 F4:.5 G4:2 E4:.5 C4:.5 F4:.5 E4:.5 D4:2 C4:3',
    chords: [['-', 3], ...bars('C C G G G G G C F C G C F C G C', 3)],
  },
  {
    id: 'auld', title: 'Auld Lang Syne', note: 'Lagu rakyat Skotlandia', bpm: 84, style: 'arp8',
    melody: 'r:3 D4:1 G4:1.5 G4:.5 G4:1 B4:1 A4:1.5 G4:.5 A4:1 B4:.5 A4:.5 G4:1.5 G4:.5 B4:1 D5:1 E5:3 E5:1 '
      + 'D5:1.5 B4:.5 B4:1 G4:1 A4:1.5 G4:.5 A4:1 B4:.5 A4:.5 G4:1.5 E4:.5 E4:1 D4:1 G4:3 E5:1 '
      + 'D5:1.5 B4:.5 B4:1 G4:1 A4:1.5 G4:.5 A4:1 E5:1 D5:1.5 B4:.5 B4:1 D5:1 E5:3 G5:1 '
      + 'D5:1.5 B4:.5 B4:1 G4:1 A4:1.5 G4:.5 A4:1 B4:.5 A4:.5 G4:1.5 E4:.5 E4:1 D4:1 G4:4',
    chords: [['-', 4], ...bars('G D G C G D', 4), ['Em', 2], ['D', 2], ['G', 4], ...bars('G D G C G D', 4), ['C', 2], ['D', 2], ['G', 4]],
  },
  {
    id: 'sakura', title: 'Sakura Sakura', note: 'Lagu rakyat Jepang', bpm: 66, style: 'arp8', shift: 12,
    melody: 'A4:1 A4:1 B4:2 A4:1 A4:1 B4:2 A4:1 B4:1 C5:1 B4:1 A4:1 B4:.5 A4:.5 F4:2 E4:1 C4:1 E4:1 F4:1 E4:1 E4:.5 C4:.5 B3:2 '
      + 'A4:1 B4:1 C5:1 B4:1 A4:1 B4:.5 A4:.5 F4:2 E4:1 C4:1 E4:1 F4:1 E4:1 E4:.5 C4:.5 B3:2 '
      + 'A4:1 A4:1 B4:2 A4:1 A4:1 B4:2 E4:1 F4:1 B4:.5 A4:.5 F4:1 E4:4',
    chords: bars('Am Am Am F Am E Am F Am E Am Am Dm E', 4),
  },
];

// Recordings made for the Puskesmas (files in public/music, loudness-matched at about -16 LUFS).
export type Recording = { id: string; title: string; note: string; src: string };
export const RECORDINGS: Recording[] = [
  { id: 'mars', title: 'Mars Puskesmas', note: 'Rekaman UPTD Puskesmas Karang Rejo', src: '/music/mars-puskesmas.mp3' },
  { id: 'karang-rejo', title: 'Puskesmas Karang Rejo', note: 'farida_kurnia1971 · dibuat dengan Suno', src: '/music/puskesmas-karang-rejo.mp3' },
  { id: 'pelayanan-1', title: 'Pelayanan Kami (versi 1)', note: 'farida_kurnia1971 · dibuat dengan Suno', src: '/music/pelayanan-kami-1.mp3' },
  { id: 'pelayanan-2', title: 'Pelayanan Kami (versi 2)', note: 'farida_kurnia1971 · dibuat dengan Suno', src: '/music/pelayanan-kami-2.mp3' },
];

// The whole playlist: the Puskesmas songs first, then the guitar pieces.
export type Item = ({ kind: 'recording' } & Recording) | ({ kind: 'guitar' } & Song);
export const PLAYLIST: Item[] = [
  ...RECORDINGS.map((r) => ({ kind: 'recording' as const, ...r })),
  ...SONGS.map((song) => ({ kind: 'guitar' as const, ...song })),
];
