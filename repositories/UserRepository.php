<?php

class UserRepository implements IUserRepository
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    // ============================================================
    // AUTH
    // ============================================================

    public function findByEmail(string $email): ?UserDTO
    {
        $sql  = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":email" => $email]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) return null;

        return $this->mapToDTO($data);
    }
    public function getAdmins(): array
{
    $sql  = "SELECT * FROM users WHERE is_admin = true AND is_active = true";
    $stmt = $this->conn->query($sql);
    return array_map([$this, 'mapToDTO'], $stmt->fetchAll(PDO::FETCH_ASSOC));
}

    /**
     * Igual ao findByEmail mas devolve um objeto com password_hash
     * para que o service possa fazer password_verify().
     * Nunca expor este método para fora do UserService.
     */
    public function findByEmailWithHash(string $email): ?object
    {
        $sql  = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":email" => $email]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) return null;

        return (object) [
            "id"            => $data['id'],
            "password_hash" => $data['password_hash']
        ];
    }
    // Implementação UserRepository.php
    public function findRandomExcluding(array $excludeIds, int $limit): array
    {
        if ($limit <= 0) return [];

        if (!empty($excludeIds)) {
            $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
            $sql = "SELECT * FROM utilizadores 
                    WHERE id NOT IN ($placeholders) 
                    AND is_admin = false 
                    AND is_active = true 
                    ORDER BY RANDOM() 
                    LIMIT ?";
            $params = array_merge(array_values($excludeIds), [$limit]);
        } else {
            $sql = "SELECT * FROM utilizadores 
                    WHERE is_admin = false 
                    AND is_active = true 
                    ORDER BY RANDOM() 
                    LIMIT ?";
            $params = [$limit];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // usar o mesmo método de mapeamento já utilizado em findById/getAllUsers
        return array_map(fn($row) => $this->mapToDTO($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
    public function findByUsername(string $username): ?UserDTO
    {
        $sql  = "SELECT * FROM users WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":username" => $username]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) return null;

        return $this->mapToDTO($data);
    }

    public function findById(string $id): ?UserDTO
    {
        $sql  = "SELECT * FROM users WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":id" => $id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) return null;

        return $this->mapToDTO($data);
    }

    public function create(User $user): bool
    {
        $sql = "
            INSERT INTO users (
                id, nome, username, email, password_hash,
                data_nascimento, genero, privacidade,
                is_admin, is_active, criado_em, atualizado_em
            ) VALUES (
                :id, :nome, :username, :email, :password_hash,
                :data_nascimento, :genero, :privacidade,
                :is_admin, :is_active, :criado_em, :atualizado_em
            )
        ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ":id"              => $user->id,
            ":nome"            => $user->nome,
            ":username"        => $user->username,
            ":email"           => $user->email,
            ":password_hash"   => $user->password_hash,
            ":data_nascimento" => $user->data_nascimento,
            ":genero"          => $user->genero,
            ":privacidade"     => $user->privacidade,
            ":is_admin"        => $user->is_admin  ? 'true' : 'false',
            ":is_active"       => $user->is_active ? 'true' : 'false',
            ":criado_em"       => $user->criado_em,
            ":atualizado_em"   => $user->atualizado_em
        ]);
    }

    // ============================================================
    // EMAIL VERIFICATION
    // ============================================================

    public function verifyEmail(string $userId): bool
    {
        $sql = "UPDATE users SET email_verificado_em = NOW() WHERE id = :id";
        return $this->conn->prepare($sql)->execute([":id" => $userId]);
    }

    // ============================================================
    // PASSWORD
    // ============================================================

    public function updatePassword(string $userId, string $passwordHash): bool
    {
        $sql = "UPDATE users SET password_hash = :p WHERE id = :id";
        return $this->conn->prepare($sql)->execute([
            ":p"  => $passwordHash,
            ":id" => $userId
        ]);
    }

    // ============================================================
    // PROFILE
    // ============================================================

    public function updateProfile(string $userId, User $user): bool
    {
        $sql = "
            UPDATE users
            SET nome = :nome,
                bio  = :bio,
                privacidade = :privacidade
            WHERE id = :id
        ";

        return $this->conn->prepare($sql)->execute([
            ":nome"       => $user->nome,
            ":bio"        => $user->bio,
            ":privacidade"=> $user->privacidade,
            ":id"         => $userId
        ]);
    }

    public function updateFotoPerfil(string $userId, string $url): bool
    {
        $sql = "UPDATE users SET foto_perfil = :url WHERE id = :id";
        return $this->conn->prepare($sql)->execute([":url" => $url, ":id" => $userId]);
    }

    public function updateFotoCapa(string $userId, string $url): bool
    {
        $sql = "UPDATE users SET foto_capa = :url WHERE id = :id";
        return $this->conn->prepare($sql)->execute([":url" => $url, ":id" => $userId]);
    }

    public function updateUltimoAcesso(string $userId): bool
    {
        $sql = "UPDATE users SET ultimo_acesso_em = NOW() WHERE id = :id";
        return $this->conn->prepare($sql)->execute([":id" => $userId]);
    }

    // ============================================================
    // ADMIN
    // ============================================================

    public function getAllUsers(): array
    {
        $sql  = "SELECT * FROM users ORDER BY criado_em DESC";
        $stmt = $this->conn->query($sql);
        return array_map([$this, "mapToDTO"], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function updateUser(string $userId, User $user): bool
    {
        $sql = "UPDATE users SET nome=:nome, username=:username, email=:email WHERE id=:id";
        return $this->conn->prepare($sql)->execute([
            ":nome"     => $user->nome,
            ":username" => $user->username,
            ":email"    => $user->email,
            ":id"       => $userId
        ]);
    }

    public function tornarAdmin(string $userId): bool
    {
        $sql = "UPDATE users SET is_admin = true WHERE id = :id";

        return $this->conn->prepare($sql)->execute([
            ":id" => $userId
        ]);
    }

    public function removerAdmin(string $userId): bool
    {
        $sql = "UPDATE users SET is_admin = false WHERE id = :id";

        return $this->conn->prepare($sql)->execute([
            ":id" => $userId
        ]);
    }
    public function activateUser(string $userId): bool
    {
        $sql = "UPDATE users SET is_active = true WHERE id = :id";
        return $this->conn->prepare($sql)->execute([":id" => $userId]);
    }

    public function deactivate(string $userId): bool
    {
        $sql = "UPDATE users SET is_active = false WHERE id = :id";
        return $this->conn->prepare($sql)->execute([":id" => $userId]);
    }
    public function delete(string $userId): bool
    {
        $sql = "DELETE FROM users WHERE id = :id";
    
        return $this->conn->prepare($sql)->execute([
            ":id" => $userId
        ]);
    }
    public function searchUsers(string $query): array
    {
        $sql  = "SELECT * FROM users WHERE nome LIKE :q OR username LIKE :q";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":q" => "%$query%"]);
        return array_map([$this, "mapToDTO"], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function countUsers(): int
    {
        return (int) $this->conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }
        public function removerFotoPerfil(string $userId): bool
    {
        $sql = "UPDATE users SET foto_perfil = NULL WHERE id = :id";
        return $this->conn->prepare($sql)->execute([":id" => $userId]);
    }

    public function getAdminMetrics(): array
    {
        // Total de utilizadores
        $totalUsers = $this->conn->query("SELECT COUNT(*) FROM users")->fetchColumn();

        // Utilizadores ativos hoje (últimos 24h)
        $activeToday = $this->conn->query("
            SELECT COUNT(DISTINCT id)
            FROM users
            WHERE ultimo_acesso >= NOW() - INTERVAL 1 DAY
        ")->fetchColumn();

        // Utilizadores ativos semana
        $activeWeek = $this->conn->query("
            SELECT COUNT(DISTINCT id)
            FROM users
            WHERE ultimo_acesso >= NOW() - INTERVAL 7 DAY
        ")->fetchColumn();

        // Utilizadores ativos mês
        $activeMonth = $this->conn->query("
            SELECT COUNT(DISTINCT id)
            FROM users
            WHERE ultimo_acesso >= NOW() - INTERVAL 30 DAY
        ")->fetchColumn();

        // Novos registos por dia (últimos 7 dias)
        $newUsersPerDay = $this->conn->query("
            SELECT DATE(criado_em) as dia, COUNT(*) as total
            FROM users
            WHERE criado_em >= NOW() - INTERVAL 7 DAY
            GROUP BY DATE(criado_em)
            ORDER BY dia DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Taxa de crescimento (%)
        $lastMonth = $this->conn->query("
            SELECT COUNT(*) FROM users
            WHERE criado_em < NOW() - INTERVAL 30 DAY
        ")->fetchColumn();

        $thisMonth = $this->conn->query("
            SELECT COUNT(*) FROM users
            WHERE criado_em >= NOW() - INTERVAL 30 DAY
        ")->fetchColumn();

        $growthRate = $lastMonth > 0
            ? (($thisMonth - $lastMonth) / $lastMonth) * 100
            : 100;

        return [
            "total_users" => (int)$totalUsers,
            "active_today" => (int)$activeToday,
            "active_week" => (int)$activeWeek,
            "active_month" => (int)$activeMonth,
            "new_users_per_day" => $newUsersPerDay,
            "growth_rate" => round($growthRate, 2)
        ];
    }

    public function removerFotoCapa(string $userId): bool
    {
        $sql = "UPDATE users SET foto_capa = NULL WHERE id = :id";
        return $this->conn->prepare($sql)->execute([":id" => $userId]);
    }
    // ============================================================
    // MAPPER PRIVADO
    // ============================================================

    private function mapToDTO(array $data): UserDTO
    {
        return new UserDTO(
            id:                  $data['id'],
            nome:                $data['nome'],
            username:            $data['username'],
            email:               $data['email'],
            foto_perfil:         $data['foto_perfil']         ?? null,
            foto_capa:           $data['foto_capa']           ?? null,
            bio:                 $data['bio']                 ?? null,
            data_nascimento:     $data['data_nascimento']     ?? null,
            genero:              $data['genero'],
            privacidade:         $data['privacidade'],
            is_admin:            (bool) $data['is_admin'],
            is_active:           (bool) $data['is_active'],
            email_verificado_em: $data['email_verificado_em'] ?? null,
            ultimo_acesso_em:    $data['ultimo_acesso_em']    ?? null,
            criado_em:           $data['criado_em'],
            atualizado_em:       $data['atualizado_em']
        );
    }
}
