<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;

final class Candidate
{
    public static function getByElection(int $electionId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT c.*,
                    (SELECT COUNT(*) FROM votes v WHERE v.candidate_id = c.id) AS vote_count
             FROM candidates c
             WHERE c.election_id = :eid
             ORDER BY c.candidate_number ASC'
        );
        $stmt->execute([':eid' => $electionId]);
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM candidates WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function existsNumberInElection(int $electionId, int $candidateNumber, ?int $excludeId = null): bool
    {
        $pdo = Database::connection();
        $sql = 'SELECT COUNT(*) FROM candidates WHERE election_id = :eid AND candidate_number = :num';
        $params = [':eid' => $electionId, ':num' => $candidateNumber];

        if ($excludeId !== null) {
            $sql .= ' AND id != :ex';
            $params[':ex'] = $excludeId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare(
            'INSERT INTO candidates (election_id, candidate_number, name, nim, jenjang, program_studi, tagline, photo_path, vision, mission, created_at, updated_at)
             VALUES (:election_id, :num, :name, :nim, :jenjang, :prodi, :tagline, :photo, :vision, :mission, :created, :updated)'
        );
        $stmt->execute([
            ':election_id' => (int) $data['election_id'],
            ':num'         => (int) $data['candidate_number'],
            ':name'        => trim((string) $data['name']),
            ':nim'         => trim((string) $data['nim']),
            ':jenjang'     => trim((string) ($data['jenjang'] ?? 'S1')),
            ':prodi'       => trim((string) $data['program_studi']),
            ':tagline'     => trim((string) ($data['tagline'] ?? '')),
            ':photo'       => $data['photo_path'] ?? null,
            ':vision'      => trim((string) $data['vision']),
            ':mission'     => trim((string) $data['mission']),
            ':created'     => $now,
            ':updated'     => $now,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::connection();
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare(
            'UPDATE candidates
             SET candidate_number = :num,
                 name = :name,
                 nim = :nim,
                 jenjang = :jenjang,
                 program_studi = :prodi,
                 tagline = :tagline,
                 photo_path = :photo,
                 vision = :vision,
                 mission = :mission,
                 updated_at = :updated
             WHERE id = :id'
        );
        $stmt->execute([
            ':num'     => (int) $data['candidate_number'],
            ':name'    => trim((string) $data['name']),
            ':nim'     => trim((string) $data['nim']),
            ':jenjang' => trim((string) ($data['jenjang'] ?? 'S1')),
            ':prodi'   => trim((string) $data['program_studi']),
            ':tagline' => trim((string) ($data['tagline'] ?? '')),
            ':photo'   => $data['photo_path'] ?? null,
            ':vision'  => trim((string) $data['vision']),
            ':mission' => trim((string) $data['mission']),
            ':updated' => $now,
            ':id'      => $id,
        ]);
    }

    public static function hasVotes(int $id): bool
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM votes WHERE candidate_id = :id');
        $stmt->execute([':id' => $id]);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    public static function delete(int $id): bool
    {
        if (self::hasVotes($id)) {
            return false;
        }
        $pdo = Database::connection();
        $stmt = $pdo->prepare('DELETE FROM candidates WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
}

